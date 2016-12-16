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
namespace Plugin\DataImport\Helper\DataImportHistoryHelper;

use Eccube\Common\Constant;
use Eccube\Entity\Order;
use Plugin\DataImport\Entity\DataImport;
use Plugin\DataImport\Entity\DataImportSnapshot;
use Plugin\DataImport\Entity\DataImportStatus;
use Plugin\DataImport\Repository\DataImportStatusRepository;

/**
 * ポイント履歴ヘルパー
 * Class DataImportHistoryHelper
 * @package Plugin\DataImport\Helper\DataImportHistoryHelper
 */
class DataImportHistoryHelper
{
    // 保存内容(場所)
    const HISTORY_MESSAGE_MANUAL_EDIT = 'ポイント(手動変更)';
    const HISTORY_MESSAGE_EDIT = 'ポイント';
    const HISTORY_MESSAGE_USE_POINT = 'ポイント';
    const HISTORY_MESSAGE_ORDER_EDIT = 'ポイント(受注内容変更)';

    // 保存内容(ポイント種別)
    const HISTORY_MESSAGE_TYPE_CURRENT = '保有';
    const HISTORY_MESSAGE_TYPE_ADD = '加算';
    const HISTORY_MESSAGE_TYPE_PRE_USE = '仮利用';
    const HISTORY_MESSAGE_TYPE_USE = '利用';

    // 保存内容(ポイント種別)
    const STATE_CURRENT = 1; // 会員編集画面から手動更新される保有ポイント
    const STATE_ADD = 3;    // 加算ポイント
    const STATE_USE = 4;    // 利用ポイント
    const STATE_PRE_USE = 5;    // 仮利用ポイント(購入中に利用ポイントとして登録されるポイント)

    protected $app;                 // アプリケーション
    protected $entities;            // 保存時エンティティコレクション
    protected $currentActionName;   // 保存時保存動作(場所 + ポイント種別)
    protected $historyType;         // 保存種別( integer )
    protected $historyActionType;   // 保存ポイント種別( string )

    /**
     * DataImportHistoryHelper constructor.
     */
    public function __construct($app)
    {
        $this->app = $app;
        // 全てINSERTのために保存用エンティティを再生成
        $this->refreshEntity();
        // ポイント基本情報設定値
        $this->entities['DataImportInfo'] = $this->app['eccube.plugin.dataimport.repository.dataimportinfo']->getLastInsertData();
    }

    /**
     * 履歴保存エンティティを新規作成
     *  - 履歴では常にINSERTのため
     */
    public function refreshEntity()
    {
        $this->entities = array();
        $this->entities['SnapShot'] = new DataImportSnapshot();
        $this->entities['DataImport'] = new DataImport();
        $this->entities['DataImportInfo'] = $this->app['eccube.plugin.dataimport.repository.dataimportinfo']->getLastInsertData();
    }

    /**
     * 計算に必要なエンティティを追加
     * @param $entity
     */
    public function addEntity($entity)
    {
        $entityName = explode('\\', get_class($entity));
        $this->entities[array_pop($entityName)] = $entity;

        return;
    }

    /**
     * 保持エンティティコレクションを返却
     * @return mixed
     */
    public function getEntities()
    {
        return $this->entities;
    }

    /**
     * キーをもとに該当エンティティを削除
     * @param $targetName
     */
    public function removeEntity($targetName)
    {
        if (in_array($targetName, $this->entities[$targetName], true)) {
            unset($this->entities[$targetName]);
        }

        return;
    }

    /**
     * 加算ポイントの履歴登録
     *  - 受注管理画面
     * @param $dataimport
     */
    public function saveAddDataImportByOrderEdit($dataimport)
    {
        $this->currentActionName = self::HISTORY_MESSAGE_ORDER_EDIT;
        $this->historyActionType = self::HISTORY_MESSAGE_TYPE_ADD;
        $this->historyType = self::STATE_ADD;
        $this->saveHistoryDataImport($dataimport);
    }

    /**
     * 加算ポイントの履歴登録
     *  - フロント画面
     * @param $dataimport
     */
    public function saveAddDataImport($dataimport)
    {
        $this->currentActionName = self::HISTORY_MESSAGE_EDIT;
        $this->historyActionType = self::HISTORY_MESSAGE_TYPE_ADD;
        $this->historyType = self::STATE_ADD;
        $this->saveHistoryDataImport($dataimport);
    }

    /**
     * 仮利用ポイント履歴登録
     *  - フロント画面
     * @param $dataimport
     */
    public function savePreUseDataImport($dataimport)
    {
        $this->currentActionName = self::HISTORY_MESSAGE_USE_POINT;
        $this->historyActionType = self::HISTORY_MESSAGE_TYPE_PRE_USE;
        $this->historyType = self::STATE_PRE_USE;
        $this->saveHistoryDataImport($dataimport);
    }

    /**
     * 利用ポイント履歴登録
     *  - フロント画面
     * @param $dataimport
     */
    public function saveUseDataImport($dataimport)
    {
        $this->currentActionName = self::HISTORY_MESSAGE_EDIT;
        $this->historyActionType = self::HISTORY_MESSAGE_TYPE_USE;
        $this->historyType = self::STATE_USE;
        $this->saveHistoryDataImport($dataimport);
    }

    /**
     * 手動登録(管理者)ポイント履歴登録
     *  - 管理画面・会員登録/編集
     * @param $dataimport
     */
    public function saveManualDataImport($dataimport)
    {
        $this->currentActionName = self::HISTORY_MESSAGE_MANUAL_EDIT;
        $this->historyActionType = self::HISTORY_MESSAGE_TYPE_CURRENT;
        $this->historyType = self::STATE_CURRENT;
        $this->saveHistoryDataImport($dataimport);
    }

    /**
     * 受注編集による利用ポイント変更の保存
     * @param $dataimport
     */
    public function saveUseDataImportByOrderEdit($dataimport)
    {
        $this->currentActionName = self::HISTORY_MESSAGE_ORDER_EDIT;
        $this->historyActionType = self::HISTORY_MESSAGE_TYPE_USE;
        $this->historyType = self::STATE_USE;
        $this->saveHistoryDataImport($dataimport);
    }

    /**
     * 履歴登録共通処理
     * @param $dataimport
     * @return bool
     */
    protected function saveHistoryDataImport($dataimport)
    {
        // 引数判定
        if (!$this->hasEntity('Customer')) {
            return false;
        }
        if (!$this->hasEntity('DataImportInfo')) {
            return false;
        }
        if (isset($this->entities['Order'])) {
            $this->entities['DataImport']->setOrder($this->entities['Order']);
        }
        $this->entities['DataImport']->setPlgDataImportId(null);
        $this->entities['DataImport']->setCustomer($this->entities['Customer']);
        $this->entities['DataImport']->setDataImportInfo($this->entities['DataImportInfo']);
        $this->entities['DataImport']->setPlgDynamicDataImport((integer)$dataimport);
        $this->entities['DataImport']->setPlgDataImportActionName($this->historyActionType.$this->currentActionName);
        $this->entities['DataImport']->setPlgDataImportType($this->historyType);
        $this->app['orm.em']->persist($this->entities['DataImport']);
        $this->app['orm.em']->flush($this->entities['DataImport']);
        $this->app['orm.em']->clear($this->entities['DataImport']);
        return true;
    }

    /**
     * スナップショット情報登録
     * @param $dataimport
     * @return bool
     */
    public function saveSnapShot($dataimport)
    {
        // 必要エンティティ判定
        if (!$this->hasEntity('Customer')) {
            return false;
        }
        $this->entities['SnapShot']->setPlgDataImportSnapshotId(null);
        $this->entities['SnapShot']->setCustomer($this->entities['Customer']);
        $this->entities['SnapShot']->setOrder($this->hasEntity('Order') ? $this->entities['Order'] : null);
        $this->entities['SnapShot']->setPlgDataImportAdd($dataimport['add']);
        $this->entities['SnapShot']->setPlgDataImportCurrent((integer)$dataimport['current']);
        $this->entities['SnapShot']->setPlgDataImportUse($dataimport['use']);
        $this->entities['SnapShot']->setPlgDataImportSnapActionName($this->currentActionName);
        $this->app['orm.em']->persist($this->entities['SnapShot']);
        $this->app['orm.em']->flush($this->entities['SnapShot']);
        return true;
    }

    /**
     * エンティティの有無を確認
     *  - 引数で渡された値をキーにエンティティの有無を確認
     * @param $name
     * @return bool
     */
    protected function hasEntity($name)
    {
        if (isset($this->entities[$name])) {
            return true;
        }

        return false;
    }

    /**
     * 付与ポイントのステータスレコードを追加する
     * @return bool
     */
    public function saveDataImportStatus()
    {
        $this->entities['DataImportStatus'] = new DataImportStatus();
        if (isset($this->entities['Order'])) {
            $this->entities['DataImportStatus']->setOrderId($this->entities['Order']->getId());
        }
        if (isset($this->entities['Customer'])) {
            $this->entities['DataImportStatus']->setCustomerId($this->entities['Customer']->getId());
        }
        $this->entities['DataImportStatus']->setStatus(DataImportStatusRepository::POINT_STATUS_UNFIX);
        $this->entities['DataImportStatus']->setDelFlg(Constant::DISABLED);
        $this->entities['DataImportStatus']->setDataImportFixDate(null);
        $this->app['orm.em']->persist($this->entities['DataImportStatus']);
        $this->app['orm.em']->flush($this->entities['DataImportStatus']);
        $this->app['orm.em']->clear($this->entities['DataImportStatus']);
        return true;
    }

    /**
     *  ポイントステータスを確定状態にする
     */
    public function fixDataImportStatus()
    {
        $orderId = $this->entities['Order']->getId();
        $DataImportStatus = $this->app['eccube.plugin.dataimport.repository.dataimportstatus']->findOneBy(
            array('order_id' => $orderId)
        );
        if (!$DataImportStatus instanceof DataImportStatus) {
            $DataImportStatus = new DataImportStatus();
            $DataImportStatus->setDelFlg(Constant::DISABLED);
            $DataImportStatus->setOrderId($this->entities['Order']->getId());
            $DataImportStatus->setCustomerId($this->entities['Customer']->getId());
            $this->app['orm.em']->persist($DataImportStatus);
        }
        /** @var DataImportStatus $dataimportStatus */
        $DataImportStatus->setStatus($this->app['eccube.plugin.dataimport.repository.dataimportstatus']->getFixStatusValue());
        $DataImportStatus->setDataImportFixDate(new \DateTime());
        $this->app['orm.em']->flush($DataImportStatus);
    }

    /**
     *  ポイントステータスを削除状態にする
     * @param Order $order 対象オーダー
     */
    public function deleteDataImportStatus(Order $order)
    {
        $orderId = $order->getId();
        $dataimportStatus = $this->app['eccube.plugin.dataimport.repository.dataimportstatus']->findOneBy(
            array('order_id' => $orderId)
        );
        if (!$dataimportStatus) {
            return;
        }
        /** @var DataImportStatus $dataimportStatus */
        $dataimportStatus->setDelFlg(Constant::ENABLED);
        $this->app['orm.em']->flush($dataimportStatus);
    }
}
