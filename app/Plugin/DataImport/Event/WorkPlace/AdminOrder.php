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
use Symfony\Component\Form\FormBuilder;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * フックデータインポート汎用処理具象クラス
 *  - 拡張元 : 受注登録( 編集 )
 *  - 拡張項目 : データインポート付与判定・登録・データインポート調整
 *  - 商品明細の変更によるデータインポートの調整
 * Class AdminOrder
 * @package Plugin\DataImport\Event\WorkPlace
 */
class  AdminOrder extends AbstractWorkPlace
{
    /**
     * @var \Plugin\DataImport\Entity\DataImportInfo $DataImportInfo
     */
    protected $DataImportInfo;

    /**
     * @var \Plugin\DataImport\Helper\DataImportCalculateHelper\DataImportCalculateHelper $calculator
     */
    protected $calculator;

    /**
     * @var \Plugin\DataImport\Helper\DataImportHistoryHelper\DataImportHistoryHelper $history
     */
    protected $history;

    /**
     * AdminOrder constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->DataImportInfo = $this->app['eccube.plugin.dataimport.repository.dataimportinfo']->getLastInsertData();
        $this->calculator = $this->app['eccube.plugin.dataimport.calculate.helper.factory'];
        $this->history = $this->app['eccube.plugin.dataimport.history.service'];
    }

    /**
     * 受注登録・編集
     *
     * @param EventArgs $event
     */
    public function createForm(EventArgs $event)
    {
        return;

        $builder = $this->buildForm($event->getArgument('builder'));
        $Order = $event->getArgument('TargetOrder');
        $Customer = $Order->getCustomer();

        // 非会員受注の場合は制御を行わない.
        if (!$Customer instanceof Customer) {
            return;
        }

        $currentDataImport = $this->calculateCurrentDataImport($Order, $Customer);
        $useDataImport = $this->app['eccube.plugin.dataimport.repository.dataimport']->getLatestUseDataImport($Order);
        $useDataImport = abs($useDataImport);
        $addDataImport = 0;

        // 受注編集時
        if ($Order->getId()) {
            $addDataImport = $this->app['eccube.plugin.dataimport.repository.dataimport']->getLatestAddDataImportByOrder($Order);

            // 確定ステータスの場合
            if ($this->app['eccube.plugin.dataimport.repository.dataimportstatus']->isFixedStatus($Order)) {
                $builder->addEventListener(
                    FormEvents::POST_SUBMIT,
                    function (FormEvent $event) use ($currentDataImport, $useDataImport, $addDataImport) {
                        $form = $event->getForm();
                        $recalcCurrentDataImport = $currentDataImport + $useDataImport - $addDataImport;
                        $inputUseDataImport = $form['use_dataimport']->getData();
                        $inputAddDataImport = $form['add_dataimport']->getData();
                        if ($inputUseDataImport > $recalcCurrentDataImport + $inputAddDataImport) {
                            $error = new FormError('保有データインポート以内になるよう調整してください');
                            $form['use_dataimport']->addError($error);
                            $form['add_dataimport']->addError($error);
                        }
                    }
                );
                // 非確定ステータスの場合
            } else {
                $builder->addEventListener(
                    FormEvents::POST_SUBMIT,
                    function (FormEvent $event) use ($currentDataImport, $useDataImport) {
                        $form = $event->getForm();
                        $inputUseDataImport = $form['use_dataimport']->getData();
                        // 現在の保有データインポート + 更新前の利用データインポートが上限値
                        if ($inputUseDataImport > $currentDataImport + $useDataImport) {
                            $error = new FormError('保有データインポート以内で入力してください');
                            $form['use_dataimport']->addError($error);
                        }
                    }
                );
            }
            // 新規受注登録
        } else {
            $builder->addEventListener(
                FormEvents::POST_SUBMIT,
                function (FormEvent $event) use ($currentDataImport, $useDataImport) {
                    $form = $event->getForm();
                    $inputUseDataImport = $form['use_dataimport']->getData();
                    // 現在の保有データインポート + 更新前の利用データインポートが上限値
                    if ($inputUseDataImport > $currentDataImport + $useDataImport) {
                        $error = new FormError('保有データインポート以内で入力してください');
                        $form['use_dataimport']->addError($error);
                    }
                }
            );
        }

        $builder->get('use_dataimport')->setData($useDataImport);
        $builder->get('add_dataimport')->setData($addDataImport);
    }

    /**
     * 受注登録・編集画面のフォームを生成する.
     *
     * @param $builder
     * @return mixed
     */
    protected function buildForm($builder)
    {
        return;
        $builder->add(
            'use_dataimport',
            'integer',
            array(
                'label' => '利用データインポート',
                'required' => false,
                'mapped' => false,
                'attr' => array(
                    'class' => 'form-control',
                ),
                'constraints' => array(
                    new Assert\GreaterThanOrEqual(array('value' => 0)),
                    new Assert\Length(
                        array(
                            'max' => $this->app['config']['int_len'],
                        )
                    ),
                ),
            )
        )->add(
            'add_dataimport',
            'integer',
            array(
                'label' => '加算データインポート',
                'required' => false,
                'mapped' => false,
                'attr' => array(
                    'class' => 'form-control',
                ),
                'constraints' => array(
                    new Assert\GreaterThanOrEqual(array('value' => 0)),
                    new Assert\Length(
                        array(
                            'max' => $this->app['config']['int_len'],
                        )
                    ),
                ),
            )
        );

        return $builder;
    }

    public function createTwig(TemplateEvent $event)
    {
        return;
        $parameters = $event->getParameters();

        $Order = $parameters['Order'];
        $Customer = $Order->getCustomer();

        // 会員情報が設定されていない場合はデータインポート関連の情報は表示しない.
        if (!$Customer instanceof Customer) {
            return;
        }

        $parameters = $event->getParameters();
        $source = $event->getSource();

        // フォーム項目の追加.
        $search = '<dl id="product_info_result_box__body_summary"';
        $view = 'DataImport/Resource/template/admin/Event/AdminOrder/order_dataimport.twig';
        $snippet = $this->app['twig']->getLoader()->getSource($view);
        $replace = $snippet.$search;
        $source = str_replace($search, $replace, $source);

        // 保有データインポートの追加
        $search = '<div id="product_info_box"';
        $view = 'DataImport/Resource/template/admin/Event/AdminOrder/order_current_dataimport.twig';
        $snippet = $this->app['twig']->getLoader()->getSource($view);
        $replace = $snippet.$search;
        $source = str_replace($search, $replace, $source);

        // キャンセル時のデータインポート動作追記
        $search = '<div id="number_info_box__order_status_info" class="small text-danger">キャンセルの場合は在庫数を手動で戻してください</div>';
        $view = 'DataImport/Resource/template/admin/Event/AdminOrder/order_dataimport_notes.twig';
        $snippet = $this->app['twig']->getLoader()->getSource($view);
        $replace = $search.$snippet;
        $source = str_replace($search, $replace, $source);

        $orderIds = $this->app['eccube.plugin.dataimport.repository.dataimportstatus']->selectOrderIdsWithFixedByCustomer(
            $Customer->getId()
        );
        $currentDataImport = $this->app['eccube.plugin.dataimport.repository.dataimport']->calcCurrentDataImport(
            $Customer->getId(),
            $orderIds
        );

        $parameters['currentDataImport'] = number_format($currentDataImport);
        $event->setParameters($parameters);
        $event->setSource($source);
    }

    public function save(EventArgs $event)
    {
        return;

        $form = $event->getArgument('form');
        $Order = $event->getArgument('TargetOrder');
        $Customer = $Order->getCustomer();

        // 会員情報が設定されていない場合はデータインポート関連の処理は行わない
        if (!$Customer instanceof Customer) {
            return;
        }

        $useDataImport = $form['use_dataimport']->getData();
        if (is_null($useDataImport)) {
            $useDataImport = 0;
        }
        $addDataImport = $form['add_dataimport']->getData();
        if (is_null($addDataImport)) {
            $addDataImport = 0;
        }

        $beforeAddDataImport = $this->app['eccube.plugin.dataimport.repository.dataimport']
            ->getLatestAddDataImportByOrder($Order, null);

        // レコードがない場合は、レコードを新規作成
        if ($beforeAddDataImport === null) {
            $this->createAddDataImport($Order, $Customer, $addDataImport, $beforeAddDataImport);
            $this->createDataImportStatus($Order, $Customer);
        } else {
            // レコードが存在し、データインポートに相違が発生した際は、レコード更新
            if ($beforeAddDataImport !== $addDataImport) {
                $this->updateAddDataImport($Order, $Customer, $addDataImport, $beforeAddDataImport);
            }
        }

        // 利用データインポートの更新
        $this->updateUseDataImport($Order, $Customer, $useDataImport);

        // データインポートの確定処理
        if ($Order->getOrderStatus()->getId() == $this->DataImportInfo->getPlgAddDataImportStatus()) {
            $this->fixDataImport($Order, $Customer);
        }
    }

    /**
     * 受注削除
     * @param EventArgs $event
     */
    public function delete(EventArgs $event)
    {
        return;

        $Order = $event->getArgument('Order');
        $Customer = $Order->getCustomer();

        // 会員情報が設定されていない場合はデータインポート関連の処理は行わない
        if (!$Customer instanceof Customer) {
            return;
        }

        // 該当受注の利用データインポートを0で更新する.
        $this->updateUseDataImport($Order, $Customer, 0);

        // データインポートステータスを削除にする
        $this->history->deleteDataImportStatus($Order);

        // 会員データインポートの再計算
        $this->history->refreshEntity();
        $this->history->addEntity($Order);
        $this->history->addEntity($Customer);
        $currentDataImport = $this->calculateCurrentDataImport($Order, $Customer);
        $this->app['eccube.plugin.dataimport.repository.dataimportcustomer']->saveDataImport(
            $currentDataImport,
            $Customer
        );

        // SnapShot保存
        $dataimport = array();
        $dataimport['current'] = $currentDataImport;
        $dataimport['use'] = 0;
        $dataimport['add'] = 0;
        $this->saveAdjustUseOrderSnapShot($Order, $Customer, $dataimport);
    }

    /**
     * 受注編集登録でレコードがない場合に0値のレコードを作成する
     * @param Order $Order
     * @param Customer $Customer
     * @param $addDataImport
     */
    public function createAddDataImport(Order $Order, Customer $Customer, $addDataImport)
    {
        return;

        // 新しい加算データインポートの保存
        $this->history->refreshEntity();
        $this->history->addEntity($Order);
        $this->history->addEntity($Customer);
        $this->history->saveAddDataImportByOrderEdit($addDataImport);

        // 会員の保有データインポート保存
        $currentDataImport = $this->calculateCurrentDataImport($Order, $Customer);
        $this->app['eccube.plugin.dataimport.repository.dataimportcustomer']->saveDataImport(
            $currentDataImport,
            $Customer
        );

        // スナップショット保存
        $dataimport = array();
        $dataimport['current'] = $currentDataImport;
        $dataimport['use'] = 0;
        $dataimport['add'] = $addDataImport;
        $this->history->refreshEntity();
        $this->history->addEntity($Order);
        $this->history->addEntity($Customer);
        $this->history->saveSnapShot($dataimport);
    }

    /**
     * 受注編集で購入商品の構成が変更した際に以下処理を行う
     *  - 前回付与データインポートの打ち消し
     *  - 今回付与データインポートの付与
     * @param Order $Order
     * @param Customer $Customer
     * @param $newAddDataImport
     * @param $beforeAddDataImport
     */
    public function updateAddDataImport(Order $Order, Customer $Customer, $newAddDataImport, $beforeAddDataImport)
    {
        return;

        // 以前の加算データインポートをマイナスで相殺
        $this->history->addEntity($Order);
        $this->history->addEntity($Customer);
        $this->history->saveAddDataImportByOrderEdit($beforeAddDataImport * -1);

        // 新しい加算データインポートの保存
        $this->history->refreshEntity();
        $this->history->addEntity($Order);
        $this->history->addEntity($Customer);
        $this->history->saveAddDataImportByOrderEdit($newAddDataImport);

        // 会員の保有データインポート保存
        $currentDataImport = $this->calculateCurrentDataImport($Order, $Customer);
        $this->app['eccube.plugin.dataimport.repository.dataimportcustomer']->saveDataImport(
            $currentDataImport,
            $Customer
        );

        // スナップショット保存
        $dataimport = array();
        $dataimport['current'] = $currentDataImport;
        $dataimport['use'] = 0;
        $dataimport['add'] = $newAddDataImport;
        $this->history->refreshEntity();
        $this->history->addEntity($Order);
        $this->history->addEntity($Customer);
        $this->history->saveSnapShot($dataimport);
    }

    /**
     * 不適切な利用があった受注の場合の処理
     * @param Order $Order
     */
    public function checkAbuseOrder(Order $Order)
    {
        return;

        $result = $this->app['eccube.plugin.dataimport.repository.dataimportabuse']->findBy(array('order_id' => $Order->getId()));
        if (!empty($result)) {
            $this->app->addWarning('この受注は、データインポートを重複利用して購入された可能性があります。', 'admin');
        }
    }

    /**
     * データインポート確定時処理
     *  -   受注ステータス判定でデータインポートの付与が確定した際の処理
     * @param $event
     * @return bool
     */
    protected function fixDataImport(Order $Order, Customer $Customer)
    {
        return;

        // データインポートが確定ステータスなら何もしない
        if ($this->app['eccube.plugin.dataimport.repository.dataimportstatus']->isFixedStatus($Order)) {
            return;
        }

        // データインポートを確定ステータスにする
        $this->fixDataImportStatus($Order, $Customer);

        // 会員の保有データインポート更新
        $currentDataImport = $this->calculateCurrentDataImport($Order, $Customer);
        $this->app['eccube.plugin.dataimport.repository.dataimportcustomer']->saveDataImport(
            $currentDataImport,
            $Customer
        );

        // SnapShot保存
        $fixedAddDataImport = $this->app['eccube.plugin.dataimport.repository.dataimport']->getLatestAddDataImportByOrder(
            $Order
        );
        $dataimport = array();
        $dataimport['current'] = $currentDataImport;
        $dataimport['use'] = 0;
        $dataimport['add'] = $fixedAddDataImport;
        $this->saveFixOrderSnapShot($Order, $Customer, $dataimport);
    }

    /**
     * 受注の利用データインポートを新しい利用データインポートに更新する
     *  - 相違あり : 利用データインポート打ち消し、更新
     *  - 相違なし : なにもしない
     *  - 最終保存レコードがnullの場合 : 0のレコードを登録
     * @param $event
     * @return bool
     */
    protected function updateUseDataImport(Order $Order, Customer $Customer, $useDataImport)
    {
        return;

        // 更新前の利用データインポートの取得
        $beforeUseDataImport = $this->app['eccube.plugin.dataimport.repository.dataimport']->getLatestUseDataImport($Order, null);
        $beforeUseDataImport = abs($beforeUseDataImport);

        // 更新前の利用データインポートと新しい利用データインポートが同じであれば何も処理を行わない
        if ($useDataImport === $beforeUseDataImport) {
            return;
        }

        // 計算に必要なエンティティをセット
        $this->calculator->addEntity('Order', $Order);
        $this->calculator->addEntity('Customer', $Customer);
        // 計算使用値は絶対値
        $this->calculator->setUseDataImport($useDataImport);

        // 履歴保存
        // 以前のレコードがある場合は相殺処理
        if(!is_null($beforeUseDataImport)) {
            $this->history->addEntity($Order);
            $this->history->addEntity($Customer);
            $this->history->saveUseDataImportByOrderEdit($beforeUseDataImport);
        }

        // 利用データインポートを保存
        $this->history->refreshEntity();
        $this->history->addEntity($Order);
        $this->history->addEntity($Customer);
        $this->history->saveUseDataImportByOrderEdit($useDataImport * -1);

        // 会員データインポートの更新
        $currentDataImport = $this->calculateCurrentDataImport($Order, $Customer);
        $this->app['eccube.plugin.dataimport.repository.dataimportcustomer']->saveDataImport(
            $currentDataImport,
            $Customer
        );

        // SnapShot保存
        $dataimport = array();
        $dataimport['current'] = $currentDataImport;
        $dataimport['use'] = $useDataImport;
        if (!is_null($beforeUseDataImport)) {
        $dataimport['use'] = ($beforeUseDataImport - $useDataImport) * -1;
        }
        $dataimport['add'] = $this->app['eccube.plugin.dataimport.repository.dataimport']->getLatestAddDataImportByOrder($Order);;
        $this->saveAdjustUseOrderSnapShot($Order, $Customer, $dataimport);
    }

    /**
     * 付与データインポートを「確定」に変更する
     */
    protected function fixDataImportStatus(Order $Order, Customer $Customer)
    {
        return;

        // データインポートを確定状態にする
        $this->history->addEntity($Order);
        $this->history->addEntity($Customer);
        $this->history->fixDataImportStatus();
    }

    /**
     * スナップショットテーブルへの保存
     *  - 利用データインポート調整時のスナップショット
     * @param $dataimport
     * @return bool
     */
    protected function saveAdjustUseOrderSnapShot(Order $Order, Customer $Customer, $dataimport)
    {
        return;

        $this->history->refreshEntity();
        $this->history->addEntity($Order);
        $this->history->addEntity($Customer);
        $this->history->saveSnapShot($dataimport);
    }

    /**
     * スナップショットテーブルへの保存
     *  - 付与データインポート確定時のスナップショット
     * @param $dataimport
     * @return bool
     */
    protected function saveFixOrderSnapShot(Order $Order, Customer $Customer, $dataimport)
    {
        return;

        $this->history->refreshEntity();
        $this->history->addEntity($Order);
        $this->history->addEntity($Customer);
        $this->history->saveSnapShot($dataimport);
    }

    /**
     * 現在保有データインポートをログから再計算
     * @return int 保有データインポート
     */
    protected function calculateCurrentDataImport(Order $Order, Customer $Customer)
    {
        return;

        $orderIds = $this->app['eccube.plugin.dataimport.repository.dataimportstatus']->selectOrderIdsWithFixedByCustomer(
            $Customer->getId()
        );
        $currentDataImport = $this->app['eccube.plugin.dataimport.repository.dataimport']->calcCurrentDataImport(
            $Customer->getId(),
            $orderIds
        );

        if ($currentDataImport < 0) {
            // TODO: データインポートがマイナス！
            // データインポートがマイナスの時はメール送信
            $this->app['eccube.plugin.dataimport.mail.helper']->sendDataImportNotifyMail($Order, $currentDataImport);
        }

        return $currentDataImport;
    }

    /**
     * データインポートステータスのレコードを作成する
     * @param $Order 受注
     * @param $Customer 会員
     */
    private function createDataImportStatus($Order, $Customer)
    {
        return;
        
        // すでに存在するなら何もしない
        $existedStatus = $this->app['eccube.plugin.dataimport.repository.dataimportstatus']->findOneBy(
            array('order_id' => $Order->getId())
        );
        if (!empty($existedStatus)) {
            return;
        }

        // レコード作成
        $this->history->addEntity($Order);
        $this->history->addEntity($Customer);
        $this->history->saveDataImportStatus();
    }
}
