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

use Eccube\Event\EventArgs;
use Eccube\Event\TemplateEvent;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * フックデータインポート汎用処理具象クラス
 *  - 拡張元 : メール通知
 * Class AdminOrderMail
 *
 * @package Plugin\DataImport\Event\WorkPlace
 */
class  AdminOrderMail extends AbstractWorkPlace
{

    /**
     * 加算データインポート表示
     *
     * @param TemplateEvent $event
     * @return bool
     */
    public function createTwig(TemplateEvent $event)
    {

        $args = $event->getParameters();

        if (array_key_exists('Order', $args)) {
            // 個別メール通知
            $Order = $args['Order'];

        } else {
            // メール一括通知
            $ids = $args['ids'];

            $tmp = explode(',', $ids);

            $Order = $this->app['eccube.repository.order']->find($tmp[0]);
        }

        $Customer = $Order->getCustomer();
        if (empty($Customer)) {
            return false;
        }

        $body = $args['body'];

        // 利用データインポート取得
        $useDataImport = $this->app['eccube.plugin.dataimport.repository.dataimport']->getLatestUseDataImport($Order);
        $useDataImport = abs($useDataImport);

        // 加算データインポート取得.
        $addDataImport = $this->app['eccube.plugin.dataimport.repository.dataimport']->getLatestAddDataImportByOrder($Order);

        $body = $this->getBody($body, $useDataImport, $addDataImport);

        $args['body'] = $body;

        $event->setParameters($args);

    }


    /**
     * 加算データインポート表示
     *
     * @param EventArgs $event
     * @return bool
     */
    public function save(EventArgs $event)
    {

        $this->app['monolog.dataimport.admin']->addInfo('save start');


        $MailHistories = array();
        if ($event->hasArgument('Order')) {
            // 個別メール通知
            $Order = $event->getArgument('Order');
            $MailHistory = $event->getArgument('MailHistory');

            $Customer = $Order->getCustomer();
            if (empty($Customer)) {
                return false;
            }

            $MailHistories[] = $MailHistory;

        } else {
            // メール一括通知

            $ids = $event->getRequest()->get('ids');

            $ids = explode(',', $ids);

            foreach ($ids as $value) {

                $Order = $this->app['eccube.repository.order']->find($value);
                $Customer = $Order->getCustomer();
                if (empty($Customer)) {
                    continue;
                }

                $MailHistory = $this->app['eccube.repository.mail_history']->findOneBy(array('Order' => $Order), array('id' => 'DESC'));

                if (!$MailHistory) {
                    continue;
                }

                $MailHistories[] = $MailHistory;

            }

        }


        foreach ($MailHistories as $MailHistory) {

            $body = $MailHistory->getMailBody();

            $Order = $MailHistory->getOrder();

            // 利用データインポート取得
            $useDataImport = $this->app['eccube.plugin.dataimport.repository.dataimport']->getLatestUseDataImport($Order);
            $useDataImport = abs($useDataImport);

            // 加算データインポート取得.
            $addDataImport = $this->app['eccube.plugin.dataimport.repository.dataimport']->getLatestAddDataImportByOrder($Order);

            $body = $this->getBody($body, $useDataImport, $addDataImport);

            // メッセージにメールボディをセット
            $MailHistory->setMailBody($body);

            $this->app['orm.em']->flush($MailHistory);
        }


        $this->app['monolog.dataimport.admin']->addInfo('save end');
    }


    /**
     * 本文を置換
     *
     * @param $body
     * @param $useDataImport
     * @param $addDataImport
     * @return mixed
     */
    private function getBody($body, $useDataImport, $addDataImport)
    {

        // 情報置換用のキーを取得
        $search = array();
        preg_match_all('/合　計.*\\n/u', $body, $search);

        // メール本文置換
        $snippet = PHP_EOL;
        $snippet .= PHP_EOL;
        $snippet .= '***********************************************'.PHP_EOL;
        $snippet .= '　データインポート情報                                 '.PHP_EOL;
        $snippet .= '***********************************************'.PHP_EOL;
        $snippet .= PHP_EOL;
        $snippet .= '利用データインポート：'.number_format($useDataImport).' pt'.PHP_EOL;
        $snippet .= '加算データインポート：'.number_format($addDataImport).' pt'.PHP_EOL;
        $snippet .= PHP_EOL;
        $replace = $search[0][0].$snippet;
        return preg_replace('/'.$search[0][0].'/u', $replace, $body);

    }

}
