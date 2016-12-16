<?php
/*
* This file is part of EC-CUBE
*
* Copyright(c) 2000-2016 LOCKON CO.,LTD. All Rights Reserved.
* http://www.lockon.co.jp/
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace Plugin\DataImport\Event\WorkPlace;

use Eccube\Entity\Customer;
use Eccube\Entity\Order;
use Eccube\Event\EventArgs;
use Eccube\Event\TemplateEvent;
use Plugin\DataImport\Entity\DataImportAbuse;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * フックポイント汎用処理具象クラス
 *  - 拡張元 : 商品購入完了
 *  - 拡張項目 : メール内容
 * Class FrontShoppingComplete
 * @package Plugin\DataImport\Event\WorkPlace
 */
class FrontShoppingComplete extends AbstractWorkPlace
{
    /**
     * ポイントログの保存
     *  - 仮付与ポイント
     *  - 確定ポイント判定
     *  - スナップショット保存
     *  - メール送信
     * @param EventArgs $event
     * @return bool
     * @throws UndefinedFunctionException
     */
    public function save(EventArgs $event)
    {
        $this->app['monolog.dataimport']->addInfo('save start');

        $Order = $event->getArgument('Order');

        // 利用ポイントを登録
        $useDataImport = $this->app['eccube.plugin.dataimport.repository.dataimport']->getLatestPreUseDataImport($Order);
        $this->app['eccube.plugin.dataimport.history.service']->refreshEntity();
        $this->app['eccube.plugin.dataimport.history.service']->addEntity($Order);
        $this->app['eccube.plugin.dataimport.history.service']->addEntity($Order->getCustomer());
        $this->app['eccube.plugin.dataimport.history.service']->saveUseDataImport($useDataImport);

        // 保有ポイントのマイナスチェック（保有ポイント以上にポイントを利用していないか？）
        $calculateCurrentDataImport = $this->calculateCurrentDataImport($Order->getCustomer());
        if ($calculateCurrentDataImport < 0) {
            $this->app['monolog.dataimport']->addInfo('save current dataimport', array(
                    'current dataimport' => $calculateCurrentDataImport,
                )
            );
            // ポイントがマイナスの時はメール送信
            $this->app['eccube.plugin.dataimport.mail.helper']->sendDataImportNotifyMail($Order, $calculateCurrentDataImport, $useDataImport);
            // 保有ポイント以上にポイントを利用した受注であるということを記録
            $dataimportAbuse = new DataImportAbuse($Order->getId());
            $this->app['orm.em']->persist($dataimportAbuse);
            $this->app['orm.em']->flush($dataimportAbuse);
        }

        // 加算ポイントを登録
        $addDataImport = $this->calculateAddDataImport($Order, $useDataImport);
        $this->app['eccube.plugin.dataimport.history.service']->refreshEntity();
        $this->app['eccube.plugin.dataimport.history.service']->addEntity($Order);
        $this->app['eccube.plugin.dataimport.history.service']->addEntity($Order->getCustomer());
        $this->app['eccube.plugin.dataimport.history.service']->saveAddDataImport($addDataImport);
        $this->app['eccube.plugin.dataimport.history.service']->saveDataImportStatus();

        // 加算ポイントのステータスを変更（ポイント設定が確定ステータス＝新規受注の場合）
        $dataimportInfo = $this->app['eccube.plugin.dataimport.repository.dataimportinfo']->getLastInsertData();
        if ($dataimportInfo->getPlgAddDataImportStatus() == $this->app['config']['order_new']) {
            $this->app['eccube.plugin.dataimport.history.service']->fixDataImportStatus();
        }

        // 保有ポイントを再計算して、会員の保有ポイントを更新する
        $calculateCurrentDataImport = $this->calculateCurrentDataImport($Order->getCustomer());
        $this->app['eccube.plugin.dataimport.repository.dataimportcustomer']->saveDataImport(
            $calculateCurrentDataImport,
            $Order->getCustomer()
        );

        // ログ
        $this->app['monolog.dataimport']->addInfo('save add dataimport', array(
                'customer_id' => $Order->getCustomer()->getId(),
                'order_id' => $Order->getId(),
                'current dataimport' => $calculateCurrentDataImport,
                'add dataimport' => $addDataImport,
                'use dataimport' => $useDataImport,
            )
        );
        // ポイント保存用変数作成
        $dataimport = array();
        $dataimport['current'] = $calculateCurrentDataImport;
        $dataimport['use'] = $useDataImport * -1;
        $dataimport['add'] = $addDataImport;
        $this->app['eccube.plugin.dataimport.history.service']->refreshEntity();
        $this->app['eccube.plugin.dataimport.history.service']->addEntity($Order);
        $this->app['eccube.plugin.dataimport.history.service']->addEntity($Order->getCustomer());
        $this->app['eccube.plugin.dataimport.history.service']->saveSnapShot($dataimport);

        $this->app['monolog.dataimport']->addInfo('save end');
    }

    /**
     * Twig拡張処理
     * @param TemplateEvent $event
     * @return void
     */
    public function createTwig(TemplateEvent $event)
    {
        // 不適切な受注記録に、今回の受注が含まれているか？
        $parameters = $event->getParameters();
        $orderId = $parameters['orderId'];
        $result = $this->app['eccube.plugin.dataimport.repository.dataimportabuse']->findBy(array('order_id' => $orderId));
        if (empty($result)) {
            return;
        }

        // エラーメッセージの挿入
        $search = '{% endblock %}';
        $script = <<<__EOL__
{% block javascript %}
            <script>
            $(function() {
                $("#deliveradd_input_box__message").children("h2.heading01").remove();
                $("#deliveradd_input_box__message").prepend('<div class="message"><p class="errormsg bg-danger">ご注文中に問題が発生した可能性があります。お手数ですがお問い合わせをお願いします。(受注番号：{{ orderId }})</p></div>');
            });
            </script>
{% endblock %}
__EOL__;

        $replace = $search.$script;
        $source = str_replace($search, $replace, $event->getSource());
        $event->setSource($source);
    }

    /**
     * 会員の保有ポイントを計算する.
     *
     * TODO: 他のクラスでも同様の処理をしているので共通化したほうが良い
     * @param Customer $Customer
     * @return int
     */
    private function calculateCurrentDataImport($Customer)
    {
        $orderIds = $this->app['eccube.plugin.dataimport.repository.dataimportstatus']->selectOrderIdsWithFixedByCustomer(
            $Customer->getId()
        );
        $calculateCurrentDataImport = $this->app['eccube.plugin.dataimport.repository.dataimport']->calcCurrentDataImport(
            $Customer->getId(),
            $orderIds
        );

        return $calculateCurrentDataImport;
    }

    /**
     * 加算ポイントを算出する.
     *
     * @param Order $Order
     * @param int $useDataImport
     * @return int
     */
    private function calculateAddDataImport($Order, $useDataImport)
    {
        $calculator = $this->app['eccube.plugin.dataimport.calculate.helper.factory'];
        $calculator->addEntity('Order', $Order);
        $calculator->addEntity('Customer', $Order->getCustomer());
        $calculator->setUseDataImport($useDataImport * -1);

        $addDataImport = $calculator->getAddDataImportByOrder();
        if (is_null($addDataImport)) {
            $addDataImport = 0;
        }
        return $addDataImport;
    }
}
