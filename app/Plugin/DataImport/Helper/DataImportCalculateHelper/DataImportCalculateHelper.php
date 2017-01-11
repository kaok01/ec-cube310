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
namespace Plugin\DataImport\Helper\DataImportCalculateHelper;

use Eccube\Entity\Product;
use Plugin\DataImport\Entity\DataImportInfo;

/**
 * データインポート計算サービスクラス
 * Class DataImportCalculateHelper
 * @package Plugin\DataImport\Helper\DataImportCalculateHelper
 */
class DataImportCalculateHelper
{
    /** @var \Eccube\Application */
    protected $app;
    /** @var \Plugin\DataImport\Repository\DataImportInfoRepository */
    protected $dataimportInfo;
    /** @var  \Eccube\Entity\ */
    protected $entities;
    /** @var */
    protected $products;
    /** @var */
    protected $addDataImport;
    /** @var */
    protected $productRates;
    /** @var */
    protected $useDataImport;

    /**
     * DataImportCalculateHelper constructor.
     * @param \Eccube\Application $app
     */
    public function __construct(\Eccube\Application $app)
    {
        $this->app = $app;
        // データインポート情報基本設定取得
        $this->dataimportInfo = $this->app['eccube.plugin.dataimport.repository.dataimportinfo']->getLastInsertData();

        if (empty($this->dataimportInfo)) { // XXX ここのチェックは意味が無い
            return false;
        }
        $this->entities = array();
        $this->useDataImport = 0;
    }

    /**
     * 計算に必要なエンティティを追加
     * @param $name
     * @param $entity
     */
    public function addEntity($name, $entity)
    {
        $this->entities[$name] = $entity;
    }

    /**
     * 保持エンティティを返却
     * @param $name
     * @return array|bool|\Eccube\Entity\
     */
    public function getEntity($name)
    {
        if ($this->hasEntities($name)) {
            return $this->entities[$name];
        }

        return false;
    }

    /**
     * キーをもとに該当エンティティを削除
     * @param $name
     * @return bool
     */
    public function removeEntity($name)
    {
        if ($this->hasEntities($name)) {
            unset($this->entities[$name]);

            return true;
        }

        return false;
    }

    /**
     * 保持エンティティを確認
     * @param $name
     * @return bool
     */
    public function hasEntities($name)
    {
        if (isset($this->entities[$name])) {
            return true;
        }

        return false;
    }

    /**
     * 利用データインポートの設定
     * @param $useDataImport
     * @return bool
     */
    public function setUseDataImport($useDataImport)
    {
        // 引数の判定
        if (empty($useDataImport) && $useDataImport != 0) {
            return false;
        }

        // 利用データインポートがマイナスの場合は false
        if ($useDataImport < 0) {
            return false;
        }

        $this->useDataImport = $useDataImport;
        return true;
    }

    /**
     * 加算データインポートをセットする.
     *
     * @param $addDataImport
     */
    public function setAddDataImport($addDataImport)
    {
        $this->addDataImport = $addDataImport;
    }

    /**
     * データインポート計算時端数を設定に基づき計算返却
     * @param $value
     * @return bool|float
     */
    public function getRoundValue($value)
    {
        // データインポート基本設定オブジェクトの有無を確認
        if (empty($this->dataimportInfo)) {
            return false;
        }

        $roundType = $this->dataimportInfo->getPlgRoundType();

        // 切り上げ
        if ($roundType == DataImportInfo::POINT_ROUND_CEIL) {
            return ceil($value);
        }

        // 四捨五入
        if ($roundType == DataImportInfo::POINT_ROUND_ROUND) {
            return round($value, 0);
        }

        // 切り捨て
        if ($roundType == DataImportInfo::POINT_ROUND_FLOOR) {
            return floor($value);
        }
    }

    /**
     * 受注詳細情報の配列を返却
     * @return array|bool
     */
    protected function getOrderDetail()
    {
        // 必要エンティティを判定
        if (!$this->hasEntities('Order')) {
            return false;
        }

        // 全商品取得
        $products = array();
        foreach ($this->entities['Order']->getOrderDetails() as $key => $val) {
            $products[$val->getId()] = $val;
        }

        // 商品がない場合は処理をキャンセル
        if (count($products) < 1) {
            return false;
        }

        return $products;
    }

    /**
     * 仮付与データインポートを返却
     *  - 会員IDをもとに返却
     * @return int 仮付与データインポート
     */
    public function getProvisionalAddDataImport()
    {
        // 必要エンティティを判定
        if (!$this->hasEntities('Customer')) {
            return 0;
        }

        $customer_id = $this->entities['Customer']->getId();
        $orderIds = $this->app['eccube.plugin.dataimport.repository.dataimportstatus']->selectOrderIdsWithUnfixedByCustomer($customer_id);
        $provisionalDataImport = $this->app['eccube.plugin.dataimport.repository.dataimport']->calcProvisionalAddDataImport($orderIds);

        return $provisionalDataImport;
    }

    /**
     * カート情報をもとに加算データインポートを返却する.
     *
     * かートの明細単位で計算を行う
     * 商品ごとの付与率が設定されている場合は商品ごと付与率を利用する
     * 商品ごとの付与率に0が設定されている場合は加算データインポートは付与しない
     *
     * @return int
     */
    public function getAddDataImportByCart()
    {
        // カートエンティティチェック
        if (empty($this->entities['Cart'])) {
            $this->app['monolog']->critical('cart not found.');
            throw new \LogicException('cart not found.');
        }

        $this->addDataImport = 0;
        $basicRate = $this->dataimportInfo->getPlgBasicDataImportRate() / 100;

        foreach ($this->entities['Cart']->getCartItems() as $cartItem) {
            $rate = $basicRate;
            $ProductClass = $cartItem->getObject();
            $Product = $ProductClass->getProduct();
            // 商品ごとの付与率を取得
            $productRates = $this->app['eccube.plugin.dataimport.repository.dataimportproductrate']
                ->getDataImportProductRateByEntity(array($ProductClass));

            if ($productRates) {
                // 商品ごとの付与率が設定されている場合は、基本付与率ではなく、商品ごとの付与率を利用する
                $productId = $Product->getId();
                $rate = $productRates[$productId] / 100;
            }
            $addDataImport = ($ProductClass->getPrice02() * $rate) * $cartItem->getQuantity();
            $this->addDataImport += $addDataImport;
        }

        $this->addDataImport = $this->getRoundValue($this->addDataImport);
        return $this->addDataImport;
    }

    /**
     * 受注情報をもとに付与データインポートを返却
     * @return bool|int
     */
    public function getAddDataImportByOrder()
    {
        // 必要エンティティを判定
        $this->addDataImport = 0;
        if (!$this->hasEntities('Order')) {
            return false;
        }

        // 商品詳細情報ををオーダーから取得
        $this->products = $this->getOrderDetail();

        if (!$this->products) {
            // 商品詳細がなければ処理終了
            return;
        }

        // 商品ごとのデータインポート付与率を取得
        $productRates = $this->app['eccube.plugin.dataimport.repository.dataimportproductrate']->getDataImportProductRateByEntity(
            $this->products
        );

        // 付与率の設定がされていない場合
        if (count($productRates) < 1) {
            $productRates = false;
        }

        // 商品ごとのデータインポート付与率セット
        $this->productRates = $productRates;

        // 取得データインポート付与率商品ID配列を取得
        if ($this->productRates) {
            $productKeys = array_keys($this->productRates);
        }

        $basicRate = $this->dataimportInfo->getPlgBasicDataImportRate();

        // 商品詳細ごとの購入金額にレートをかける
        // レート計算後個数をかける
        foreach ($this->products as $node) {
            // 商品毎データインポート付与率が設定されていない場合
            $rate = $basicRate / 100;
            if ($this->productRates) {
                if (in_array($node->getProduct()->getId(), $productKeys)) {
                    // 商品ごとデータインポート付与率が設定されている場合
                    $rate = $this->productRates[$node->getProduct()->getId()] / 100;
                }
            }
            $this->addDataImport += ($node->getProductClass()->getPrice02() * $rate) * $node->getQuantity();
        }

        // 減算処理の場合減算値を返却
        if ($this->isSubtraction() && !empty($this->useDataImport)) {
            return $this->getSubtractionCalculate();
        }

        return $this->getRoundValue($this->addDataImport);
    }

    /**
     * 商品情報から加算データインポートを算出する.
     *
     * 商品毎の付与率がnullの場合は基本データインポート付与率で算出する
     * 商品毎の付与率が設定されている場合(0も含む)は、商品毎の付与率で算出する
     *
     * @return array
     */
    public function getAddDataImportByProduct(Product $Product)
    {
        // 商品毎の付与率を取得.
        $productRate = $this->app['eccube.plugin.dataimport.repository.dataimportproductrate']->getLastDataImportProductRateById(
            $Product->getId()
        );
        // 基本データインポート付与率を取得
        $basicRate = $this->dataimportInfo->getPlgBasicDataImportRate();

        // 商品毎の付与率あればそちらを優先
        // なければ基本データインポート付与率を利用
        $calculateRate = $basicRate;
        if (!is_null($productRate)) {
            $calculateRate = $productRate;
        }

        // 商品規格の販売価格(税抜)に応じて最小値と最大値を返却.
        $rate = array();
        $rate['min'] = (integer)$this->getRoundValue($Product->getPrice02Min() * ($calculateRate / 100));
        $rate['max'] = (integer)$this->getRoundValue($Product->getPrice02Max() * ($calculateRate / 100));

        return $rate;
    }

    /**
     * データインポート機能基本情報から計算方法を取得し判定
     * @return bool
     */
    protected function isSubtraction()
    {
        // 基本情報が設定されているか確認
        if (empty($this->dataimportInfo)) {
            return false;
        }

        // 計算方法の判定
        if ($this->dataimportInfo->getPlgCalculationType() === DataImportInfo::POINT_CALCULATE_SUBTRACTION) {
            return true;
        }

        return false;
    }

    /**
     * データインポート利用時の減算処理
     *
     * 利用データインポート数 ＊ データインポート金額換算率 ＝ データインポート値引額
     * 加算データインポート - データインポート値引き額 * 基本データインポート付与率 = 減算後加算データインポート
     *
     * データインポート利用時かつ, データインポート設定でデータインポート減算ありを選択指定た場合に, 加算データインポートの減算処理を行う.
     * 減算の計算後, プロパティのaddDataImportに減算後の加算データインポートをセットする.
     *
     * @return bool|float|void
     */
    public function getSubtractionCalculate()
    {
        // 基本情報が設定されているか確認
        if (is_null($this->dataimportInfo->getPlgCalculationType())) {
            // XXX DataImportInfo::plg_calculation_type は nullable: false なので通らないはず
            $this->app['monolog']->critical('calculation type not found.');
            throw new \LogicException('calculation type not found.');
        }

        // 利用データインポートがない場合は処理しない.
        if (empty($this->useDataImport)) {
            return $this->addDataImport;
        }

        // 利用データインポート数 ＊ データインポート金額換算率 ＝ データインポート値引額
        $dataimportDiscount = $this->useDataImport * $this->dataimportInfo->getPlgDataImportConversionRate();

        $basicRate = $this->dataimportInfo->getPlgBasicDataImportRate() / 100;
        // 加算データインポート - データインポート値引き額 * 基本データインポート付与率 = 減算後加算データインポート
        $addDataImport = $this->addDataImport - $dataimportDiscount * $basicRate;


        if ($addDataImport < 0) {
            $addDataImport = 0;
        }

        $this->addDataImport = $this->getRoundValue($addDataImport);

        return $this->addDataImport;
    }

    /**
     * 保有データインポートを返却
     * @return bool
     */
    public function getDataImport()
    {
        // 必要エンティティを判定
        if (!$this->hasEntities('Customer')) {
            return false;
        }

        $customer_id = $this->entities['Customer']->getId();
        $dataimport = $this->app['eccube.plugin.dataimport.repository.dataimportcustomer']->getLastDataImportById($customer_id);

        return $dataimport;
    }

    /**
     * データインポート基本機能設定から換算後データインポートを返却
     * @return bool|float
     */
    public function getConversionDataImport()
    {
        // 必要エンティティを判定
        if (!$this->hasEntities('Order')) {
            return false;
        }

        // 利用データインポートの確認
        if ($this->useDataImport != 0 && empty($this->useDataImport)) {
            return false;
        }

        // データインポート基本設定の確認
        if (empty($this->dataimportInfo)) {
            return false;
        }

        // 基本換金値の取得
        $dataimportRate = $this->dataimportInfo->getPlgDataImportConversionRate();

        return $this->getRoundValue($this->useDataImport * $dataimportRate);
    }

    /**
     * 受注情報と、利用データインポート・換算値から値引き額を計算し、
     * 受注情報の更新を行う
     *
     * 購入途中で何回もデータインポート履歴が発生するケースがあるため, 前回保存した履歴
     * と今回のデータインポート差分を算出し,差分が発生している場合は true を返し値引き額
     * を保存する.
     *
     * @param integer $lastUseDataImport 同じ受注で保存した履歴の最終データインポート数
     * @return bool 差分が無い場合は false を返す
     */
    public function setDiscount($lastUseDataImport)
    {
        // 必要エンティティを判定
        if (!$this->hasEntities('Order')) {
            return false;
        }

        // 利用データインポートの確認
        if ($this->useDataImport != 0 && empty($this->useDataImport)) {
            return false;
        }

        // データインポート基本設定の確認
        if (empty($this->dataimportInfo)) {
            return false;
        }

        // 受注情報に保存されている最終保存の値引き額を取得
        $currDiscount = $this->entities['Order']->getDiscount();

        // 値引き額と利用データインポート換算値を比較→相違があればデータインポート利用分相殺後利用データインポートセット
        $useDiscount = $this->getConversionDataImport();

        $diff = $currDiscount - ($lastUseDataImport * $this->dataimportInfo->getPlgDataImportConversionRate());

        if ((integer)$currDiscount != (integer)$useDiscount) {
            $mergeDiscount = $diff + $useDiscount;
            if ($mergeDiscount >= 0) {
                $this->entities['Order']->setDiscount(abs($mergeDiscount));

                return true;
            }
        }

        return false;
    }

    /**
     * データインポートを利用していたが、お届け先変更・配送業者・支払方法の変更により、
     * 支払い金額にマイナスが発生した場合に、利用しているデータインポートを打ち消し、受注の値引きを戻す.
     *
     * データインポートを利用していない場合は打ち消し処理は行わない
     *
     * @return bool データインポート利用可能な場合 false, 支払い金額がマイナスでデータインポート利用不可の場合は true を返し、データインポートを打ち消す
     * @throws \LogicException
     */
    public function calculateTotalDiscountOnChangeConditions()
    {

        $this->app['monolog.dataimport']->addInfo('calculateTotalDiscountOnChangeConditions start');

        // 必要エンティティを判定
        if (!$this->hasEntities('Order')) {
            $this->app['monolog']->critical('Order not found.');
            throw new \LogicException('Order not found.');
        }
        if (!$this->hasEntities('Customer')) {
            $this->app['monolog']->critical('Customer not found.');
            throw new \LogicException('Customer not found.');
        }
        // データインポート基本設定の確認
        if (empty($this->dataimportInfo)) {
            throw new \LogicException('DataImportInfo not found.');
        }

        $order = $this->entities['Order'];
        $customer = $this->entities['Customer'];

        $totalAmount = $order->getTotalPrice();
        // $totalAmount が正の整数の場合はデータインポート利用可能なので false を返す.
        if ($totalAmount >= 0) {
            return false;
        }

        // 最終保存仮利用データインポート
        $useDataImport = $this->app['eccube.plugin.dataimport.repository.dataimport']->getLatestPreUseDataImport($order);

        // データインポートを利用していない場合は、打ち消し処理は行わない
        if ($useDataImport == 0) {
            return false;
        }

        // 最終データインポート利用額を算出
        $dataimportDiscount = (int)$this->getRoundValue($useDataImport * $this->dataimportInfo->getPlgDataImportConversionRate());


        $this->app['monolog.dataimport']->addInfo('discount', array(
            'total' => $totalAmount,
            'dataimportDiscount' => $dataimportDiscount,
        ));

        // 利用データインポート差し引き値引き額をセット
        $this->app['eccube.service.shopping']->setDiscount($order, $dataimportDiscount);
        // キャンセルのために「0」でログテーブルを更新
        $this->app['eccube.plugin.dataimport.history.service']->addEntity($order);
        $this->app['eccube.plugin.dataimport.history.service']->addEntity($customer);
        $this->app['eccube.plugin.dataimport.history.service']->savePreUseDataImport(0);

        // 利用データインポート打ち消し後の受注情報更新
        $newOrder = $this->app['eccube.service.shopping']->calculatePrice($order);

        $this->app['orm.em']->flush($newOrder);

        $this->app['monolog.dataimport']->addInfo('calculateTotalDiscountOnChangeConditions end');

        return true;
    }
}
