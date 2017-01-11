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
namespace Eccube\Tests\Helper\DataImportCalculateHelper;

use Eccube\Application;
use Eccube\Tests\EccubeTestCase;
use Plugin\DataImport\Entity\DataImport;
use Plugin\DataImport\Entity\DataImportInfo;
use Plugin\DataImport\Helper\DataImportHistoryHelper\DataImportHistoryHelper;

/**
 * Class DataImportCalculateHelperTest
 *
 * @package Eccube\Tests\Helper\DataImportCalculateHelper
 */
class DataImportCalculateHelperTest extends EccubeTestCase
{
    /**
     * 端数計算のテスト
     */
    public function testGetRoundValue()
    {
        $calculater = $this->app['eccube.plugin.dataimport.calculate.helper.factory'];
        $DataImportInfo = $this->app['eccube.plugin.dataimport.repository.dataimportinfo']->getLastInsertData();

        // 切り上げ
        $DataImportInfo->setPlgRoundType(DataImportInfo::POINT_ROUND_CEIL);
        $this->expected = 11;
        $this->actual = $calculater->getRoundValue(10.5);
        $this->verify();
        $this->actual = $calculater->getRoundValue(10.4);
        $this->verify();
        $this->actual = $calculater->getRoundValue(10.05);
        $this->verify();
        $this->actual = $calculater->getRoundValue(10.04);
        $this->verify();

        // 切り捨て
        $DataImportInfo->setPlgRoundType(DataImportInfo::POINT_ROUND_FLOOR);
        $this->expected = 10;
        $this->actual = $calculater->getRoundValue(10.5);
        $this->verify();
        $this->actual = $calculater->getRoundValue(10.4);
        $this->verify();
        $this->actual = $calculater->getRoundValue(10.05);
        $this->verify();
        $this->actual = $calculater->getRoundValue(10.04);
        $this->verify();

        // 四捨五入(少数点第一位を四捨五入する
        $DataImportInfo->setPlgRoundType(DataImportInfo::POINT_ROUND_ROUND);
        $this->expected = 11;
        $this->actual = $calculater->getRoundValue(10.5);
        $this->verify();
        $this->expected = 10;
        $this->actual = $calculater->getRoundValue(10.4);
        $this->verify();
        $this->expected = 10;
        $this->actual = $calculater->getRoundValue(10.05);
        $this->verify();
        $this->expected = 10;
        $this->actual = $calculater->getRoundValue(10.04);
        $this->verify();
    }

    /**
     * データインポート利用時の加算データインポート減算処理のテスト
     */
    public function testGetSubtractionCalculate()
    {
        $testData = array(
            array(1, 1, 0, 0, 0, 0),
            array(1, 1, 1, 0, 0, 0),
            array(1, 1, 2, 0, 0, 0),
            array(1, 1, 0, 0, 50, 50),
            array(1, 1, 1, 0, 50, 50),
            array(1, 1, 2, 0, 50, 50),
            array(1, 1, 0, 50, 0, 0),
            array(1, 1, 1, 50, 0, 0),
            array(1, 1, 2, 50, 0, 0),
            array(1, 1, 0, 1, 50, 49),
            array(1, 1, 1, 1, 50, 50),
            array(1, 1, 2, 1, 50, 50),
            array(1, 1, 0, 49, 50, 49),
            array(1, 1, 1, 49, 50, 50),
            array(1, 1, 2, 49, 50, 50),
            array(1, 1, 0, 50, 50, 49),
            array(1, 1, 1, 50, 50, 50),
            array(1, 1, 2, 50, 50, 50),
            array(5, 5, 0, 0, 0, 0),
            array(5, 5, 1, 0, 0, 0),
            array(5, 5, 2, 0, 0, 0),
            array(5, 5, 0, 0, 50, 50),
            array(5, 5, 1, 0, 50, 50),
            array(5, 5, 2, 0, 50, 50),
            array(5, 5, 0, 50, 0, 0),
            array(5, 5, 1, 50, 0, 0),
            array(5, 5, 2, 50, 0, 0),
            array(5, 5, 0, 1, 50, 49),
            array(5, 5, 1, 1, 50, 50),
            array(5, 5, 2, 1, 50, 50),
            array(5, 5, 0, 49, 50, 37),
            array(5, 5, 1, 49, 50, 38),
            array(5, 5, 2, 49, 50, 38),
            array(5, 5, 0, 50, 50, 37),
            array(5, 5, 1, 50, 50, 38),
            array(5, 5, 2, 50, 50, 38)
        );

        /** @var $calculater \Plugin\DataImport\Helper\DataImportCalculateHelper\DataImportCalculateHelper **/
        $calculater = $this->app['eccube.plugin.dataimport.calculate.helper.factory'];
        /** @var $DataImportInfo \Plugin\DataImport\Entity\DataImportInfo **/
        $DataImportInfo = $this->app['eccube.plugin.dataimport.repository.dataimportinfo']->getLastInsertData();
        $DataImportInfo->setPlgCalculationType(DataImportInfo::POINT_CALCULATE_SUBTRACTION);

        $i = 0;
        $max = count($testData);
        for ($i = 0; $i < $max; $i++)  {
            $data = $testData[$i];

            // 基本データインポート付与率
            $DataImportInfo->setPlgBasicDataImportRate($data[0]);
            // データインポート換算レート
            $DataImportInfo->setPlgDataImportConversionRate($data[1]);
            // 端数計算方法
            $DataImportInfo->setPlgRoundType($data[2]);
            // 利用データインポート
            $this->assertTrue($calculater->setUseDataImport($data[3]));
            // 加算データインポート
            $calculater->setAddDataImport($data[4]);
            // 期待値
            $this->expected = ($data[5]);
            $this->actual = $calculater->getSubtractionCalculate();
            $this->verify('index ' . $i . ' failed.');
        }
    }

    public function testGetAddDataImportByOrder()
    {
        $testData = array(
            /**
             * - 基本データインポート付与率
             * - データインポート換算レート
             * - 端数計算方法
             * - データインポート利用
             * - データインポート減算方式
             * - 商品毎データインポート付与率
             * - 商品価格
             * - 商品個数
             * - 期待値
             */
            0 => array(1, 1, 0, 0, 1, null, 5000, 1, 50),
            1 => array(1, 1, 1, 0, 1, null, 5000, 1, 50),
            2 => array(1, 1, 2, 0, 1, null, 5000, 1, 50),
            3 => array(1, 1, 0, 50, 1, null, 5000, 1, 50),
            4 => array(1, 1, 1, 50, 1, null, 5000, 1, 50),
            5 => array(1, 1, 2, 50, 1, null, 5000, 1, 50),
            6 => array(1, 1, 0, 0, 0, null, 5000, 1, 50),
            7 => array(1, 1, 1, 0, 0, null, 5000, 1, 50),
            8 => array(1, 1, 2, 0, 0, null, 5000, 1, 50),
            9 => array(1, 1, 0, 50, 0, null, 5000, 1, 49),
            10 => array(1, 1, 1, 50, 0, null, 5000, 1, 50),
            11 => array(1, 1, 2, 50, 0, null, 5000, 1, 50),
            12 => array(1, 1, 0, 0, 1, 0, 5000, 1, 0),
            13 => array(1, 1, 1, 0, 1, 0, 5000, 1, 0),
            14 => array(1, 1, 2, 0, 1, 0, 5000, 1, 0),
            15 => array(1, 1, 0, 50, 1, 0, 5000, 1, 0),
            16 => array(1, 1, 1, 50, 1, 0, 5000, 1, 0),
            17 => array(1, 1, 2, 50, 1, 0, 5000, 1, 0),
            18 => array(1, 1, 0, 0, 0, 0, 5000, 1, 0),
            19 => array(1, 1, 1, 0, 0, 0, 5000, 1, 0),
            20 => array(1, 1, 2, 0, 0, 0, 5000, 1, 0),
            21 => array(1, 1, 0, 50, 0, 0, 5000, 1, 0),
            22 => array(1, 1, 1, 50, 0, 0, 5000, 1, 0),
            23 => array(1, 1, 2, 50, 0, 0, 5000, 1, 0),
            24 => array(1, 1, 0, 0, 1, 1, 5000, 1, 50),
            25 => array(1, 1, 1, 0, 1, 1, 5000, 1, 50),
            26 => array(1, 1, 2, 0, 1, 1, 5000, 1, 50),
            27 => array(1, 1, 0, 50, 1, 1, 5000, 1, 50),
            28 => array(1, 1, 1, 50, 1, 1, 5000, 1, 50),
            29 => array(1, 1, 2, 50, 1, 1, 5000, 1, 50),
            30 => array(1, 1, 0, 0, 0, 1, 5000, 1, 50),
            31 => array(1, 1, 1, 0, 1, 1, 5000, 1, 50),
            32 => array(1, 1, 2, 0, 1, 1, 5000, 1, 50),
            33 => array(1, 1, 0, 50, 0, 1, 5000, 1, 49),
            34 => array(1, 1, 1, 50, 0, 1, 5000, 1, 50),
            35 => array(1, 1, 2, 50, 0, 1, 5000, 1, 50),
            36 => array(5, 5, 0, 0, 1, null, 5000, 1, 250),
            37 => array(5, 5, 1, 0, 1, null, 5000, 1, 250),
            38 => array(5, 5, 2, 0, 1, null, 5000, 1, 250),
            39 => array(5, 5, 0, 50, 1, null, 5000, 1, 250),
            40 => array(5, 5, 1, 50, 1, null, 5000, 1, 250),
            41 => array(5, 5, 2, 50, 1, null, 5000, 1, 250),
            42 => array(5, 5, 0, 0, 0, null, 5000, 1, 250),
            43 => array(5, 5, 1, 0, 0, null, 5000, 1, 250),
            44 => array(5, 5, 2, 0, 0, null, 5000, 1, 250),
            45 => array(5, 5, 0, 50, 0, null, 5000, 1, 237),
            46 => array(5, 5, 1, 50, 0, null, 5000, 1, 238),
            47 => array(5, 5, 2, 50, 0, null, 5000, 1, 238),
            48 => array(5, 5, 0, 0, 1, 0, 5000, 1, 0),
            49 => array(5, 5, 1, 0, 1, 0, 5000, 1, 0),
            50 => array(5, 5, 2, 0, 1, 0, 5000, 1, 0),
            51 => array(5, 5, 0, 50, 1, 0, 5000, 1, 0),
            52 => array(5, 5, 1, 50, 1, 0, 5000, 1, 0),
            53 => array(5, 5, 2, 50, 1, 0, 5000, 1, 0),
            54 => array(5, 5, 0, 0, 0, 0, 5000, 1, 0),
            55 => array(5, 5, 1, 0, 0, 0, 5000, 1, 0),
            56 => array(5, 5, 2, 0, 0, 0, 5000, 1, 0),
            57 => array(5, 5, 0, 50, 0, 0, 5000, 1, 0),
            58 => array(5, 5, 1, 50, 0, 0, 5000, 1, 0),
            59 => array(5, 5, 2, 50, 0, 0, 5000, 1, 0),
            60 => array(5, 5, 0, 0, 1, 1, 5000, 1, 50),
            61 => array(5, 5, 1, 0, 1, 1, 5000, 1, 50),
            62 => array(5, 5, 2, 0, 1, 1, 5000, 1, 50),
            63 => array(5, 5, 0, 50, 1, 1, 5000, 1, 50),
            64 => array(5, 5, 1, 50, 1, 1, 5000, 1, 50),
            65 => array(5, 5, 2, 50, 1, 1, 5000, 1, 50),
            66 => array(5, 5, 0, 0, 0, 1, 5000, 1, 50),
            67 => array(5, 5, 1, 0, 0, 1, 5000, 1, 50),
            68 => array(5, 5, 2, 0, 0, 1, 5000, 1, 50),
            69 => array(5, 5, 0, 50, 0, 1, 5000, 1, 37),
            70 => array(5, 5, 1, 50, 0, 1, 5000, 1, 38),
            71 => array(5, 5, 2, 50, 0, 1, 5000, 1, 38),
        );

        // テストデータ生成
        $Customer = $this->createCustomer();
        $Order = $this->createOrder($Customer);

        /** @var $calculater \Plugin\DataImport\Helper\DataImportCalculateHelper\DataImportCalculateHelper **/
        $calculater = $this->app['eccube.plugin.dataimport.calculate.helper.factory'];
        /** @var $DataImportInfo \Plugin\DataImport\Entity\DataImportInfo **/
        $DataImportInfo = $this->app['eccube.plugin.dataimport.repository.dataimportinfo']->getLastInsertData();

        $calculater->addEntity('Order', $Order);

        foreach ($testData as $i => $data) {
            // 基本データインポート付与率
            $DataImportInfo->setPlgBasicDataImportRate($data[0]);
            // データインポート換算レート
            $DataImportInfo->setPlgDataImportConversionRate($data[1]);
            // 端数計算方法
            $DataImportInfo->setPlgRoundType($data[2]);
            // 利用データインポート
            $this->assertTrue($calculater->setUseDataImport($data[3]));
            // データインポート減算方式
            $DataImportInfo->setPlgCalculationType($data[4]);

            foreach ($Order->getOrderDetails() as $OrderDetail) {
                $ProductClass = $OrderDetail->getProductClass();
                $Product = $ProductClass->getProduct();
                // 商品ごとデータインポート付与率
                $this->app['eccube.plugin.dataimport.repository.dataimportproductrate']->saveDataImportProductRate($data[5], $Product);
                // 商品価格
                $ProductClass->setPrice02($data[6]);
                // 商品個数
                $OrderDetail->setQuantity($data[7]);
            }

            $this->expected = $data[8];
            $this->actual = $calculater->getAddDataImportByOrder();
            $this->verify('index ' . $i . ' failed.');
        }
    }

    public function testGetAddDataImportByCart()
    {
        $testData = array(
            /**
             * - 基本データインポート付与率
             * - 端数計算方法
             * - 商品毎データインポート付与率
             * - 商品価格
             * - 商品個数
             * - 期待値
             */
            0 => array(1, 0, null, 50, 1, 0),
            1 => array(1, 1, null, 50, 1, 1),
            2 => array(1, 2, null, 50, 1, 1),
            3 => array(1, 0, 5, 50, 1, 2),
            4 => array(1, 1, 5, 50, 1, 3),
            5 => array(1, 2, 5, 50, 1, 3),
        );

        $Product = $this->createProduct();
        $ProductClasses = $Product->getProductClasses();
        $ProductClass = $ProductClasses[0];

        /** @var $calculater \Plugin\DataImport\Helper\DataImportCalculateHelper\DataImportCalculateHelper **/
        $calculater = $this->app['eccube.plugin.dataimport.calculate.helper.factory'];
        /** @var $DataImportInfo \Plugin\DataImport\Entity\DataImportInfo **/
        $DataImportInfo = $this->app['eccube.plugin.dataimport.repository.dataimportinfo']->getLastInsertData();

        $calculater->addEntity('Cart', $this->app['eccube.service.cart']->getCart());

        foreach ($testData as $i => $data) {
            // 基本データインポート付与率
            $DataImportInfo->setPlgBasicDataImportRate($data[0]);
            // 端数計算方法
            $DataImportInfo->setPlgRoundType($data[1]);

            // 商品ごとデータインポート付与率
            $this->app['eccube.plugin.dataimport.repository.dataimportproductrate']->saveDataImportProductRate($data[2], $Product);
            // 商品価格
            $ProductClass->setPrice02($data[3]);

            // 商品個数
            $this->app['eccube.service.cart']->clear();
            $this->app['eccube.service.cart']->setProductQuantity($ProductClass, $data[4]);
            $this->app['eccube.service.cart']->save();

            $Cart = $this->app['session']->get('cart');
            $CartItems = $Cart->getCartItems();
            foreach ($CartItems as $item) {
                $item->setObject($ProductClass);
            }
            $calculater->addEntity('Cart', $Cart);

            $this->expected = $data[5];
            $this->actual = $calculater->getAddDataImportByCart();
            $this->verify('index ' . $i . ' failed.');
        }
    }

    public function testGetAddDataImportByCartWithNotfound()
    {
        try {
            $calculater = $this->app['eccube.plugin.dataimport.calculate.helper.factory'];
            $calculater->getAddDataImportByCart();
            $this->fail('Throwable to \LogicException');
        } catch (\LogicException $e) {
            $this->assertEquals('cart not found.', $e->getMessage());
        }
    }

    public function testGetAddDataImportByProduct()
    {
        $testData = array(
            /**
             * - 基本データインポート付与率
             * - 端数計算方法
             * - 商品毎データインポート付与率
             * - 商品価格(最小)
             * - 商品価格(最大)
             * - 期待値(最小)
             * - 期待値(最大)
             */
            array(1, 0, null, 50, 490, 0, 4),
            array(1, 1, null, 50, 490, 1, 5),
            array(1, 2, null, 50, 490, 1, 5),
            array(1, 0, 5, 50, 490, 2, 24),
            array(1, 1, 5, 50, 490, 3, 25),
            array(1, 2, 5, 50, 490, 3, 25),
        );

        $Product = $this->createProduct('test', 2);
        $ProductClasses = $Product->getProductClasses();
        $ProductClass = $ProductClasses[0];

        /** @var $calculater \Plugin\DataImport\Helper\DataImportCalculateHelper\DataImportCalculateHelper **/
        $calculater = $this->app['eccube.plugin.dataimport.calculate.helper.factory'];
        /** @var $DataImportInfo \Plugin\DataImport\Entity\DataImportInfo **/
        $DataImportInfo = $this->app['eccube.plugin.dataimport.repository.dataimportinfo']->getLastInsertData();

        $max = count($testData);
        for ($i = 0; $i < $max; $i++) {
            $data = $testData[$i];

            // 基本データインポート付与率
            $DataImportInfo->setPlgBasicDataImportRate($data[0]);
            // 端数計算方法
            $DataImportInfo->setPlgRoundType($data[1]);

            // 商品ごとデータインポート付与率
            $this->app['eccube.plugin.dataimport.repository.dataimportproductrate']->saveDataImportProductRate($data[2], $Product);
            // 商品価格
            $ProductClasses[0]->setPrice02($data[3]);
            $ProductClasses[1]->setPrice02($data[4]);

            $dataimport = $calculater->getAddDataImportByProduct($Product);

            // min
            $this->expected = $data[5];
            $this->actual = $dataimport['min'];
            $this->verify('index ' . $i . ' min failed.');

            // max
            $this->expected = $data[6];
            $this->actual = $dataimport['max'];
            $this->verify('index ' . $i . ' max failed.');
        }
    }

    /**
     * データインポートを利用していたが、支払い方法の変更によりマイナスが発生したので、キャンセル処理が行われた
     */
    public function testCalculateTotalDiscountOnChangeConditions()
    {
        $Customer = $this->createCustomer();
        $Order = $this->createOrder($Customer);

        // 支払い金額が1300円で、1200ptを利用する
        $Order->setSubtotal(1000);
        $Order->setCharge(300);
        $Order->setDiscount(1200);
        $Order->setDeliveryFeeTotal(0);
        $this->app['orm.em']->flush();

        $DataImportInfo = $this->app['eccube.plugin.dataimport.repository.dataimportinfo']->getLastInsertData();
        $this->createPreUseDataImport($DataImportInfo, $Customer, $Order, -1200); // 1200ptを利用

        $calculater = $this->app['eccube.plugin.dataimport.calculate.helper.factory'];
        $calculater->addEntity('Order', $Order);
        $calculater->addEntity('Customer', $Customer);

        $this->expected = false;
        $this->actual = $calculater->calculateTotalDiscountOnChangeConditions();
        $this->verify('支払い金額がプラスの場合は false');

        $this->expected = 100;
        $this->actual = $Order->getTotalPrice();
        $this->verify('お支払い金額は '.$this->actual.' 円');

        // 支払い方法を変更したことで、手数料が300円から0円になり、支払い金額にマイナスが発生した
        $Order->setCharge(0);
        $this->app['orm.em']->flush();

        // データインポートの打ち消しと、値引きの戻しが実行されているはず。
        $this->expected = true;
        $this->actual = $calculater->calculateTotalDiscountOnChangeConditions();
        $this->verify('支払い金額がマイナスの場合は true');

        // 支払い金額は手数料とデータインポート利用の値引きがなくなるので1000円になる
        $this->expected = 1000;
        $this->actual = $Order->getTotalPrice();
        $this->verify('お支払い金額は '.$this->actual.' 円');

        // 値引きはキャンセルされ、0円になる
        $this->expected = 0;
        $this->actual = $Order->getDiscount();
        $this->verify('値引きは '.$this->actual.' 円');

        // 利用データインポートは打ち消され、0ptになる
        $this->expected = 0;
        $this->actual = $this->app['eccube.plugin.dataimport.repository.dataimport']->getLatestPreUseDataImport($Order);
        $this->verify('利用データインポートは '.$this->actual.' 円');
    }

    /**
     * 10データインポート利用しようとしたが、お支払い金額がマイナスになっている場合
     */
    public function testCalculateTotalDiscountOnChangeConditionsWithUseDataImport()
    {
        $Customer = $this->createCustomer();
        $Order = $this->createOrder($Customer);

        // データインポート利用以外のプラグインで、お支払い金額がマイナスになった場合
        $totalAmount = $Order->getTotalPrice();
        $this->app['eccube.service.shopping']->setDiscount($Order, $totalAmount + 1); // 支払い金額 + 1円を値引きする
        $this->app['orm.em']->flush();

        $calculater = $this->app['eccube.plugin.dataimport.calculate.helper.factory'];
        $calculater->addEntity('Order', $Order);
        $calculater->addEntity('Customer', $Customer);

        $DataImportInfo = $this->app['eccube.plugin.dataimport.repository.dataimportinfo']->getLastInsertData();
         // 10データインポート利用する
        $this->createPreUseDataImport($DataImportInfo, $Customer, $Order, 10);

        $this->expected = true;
        $this->actual = $calculater->calculateTotalDiscountOnChangeConditions();
        $this->verify('データインポート利用以外のプラグインで、お支払い金額がマイナスになった場合は true');

        $this->expected = -11;
        $this->actual = $Order->getTotalPrice();
        $this->verify('お支払い金額は '.$this->actual.' 円');

        // 保有データインポートは 0 になっているはず
        $orderIds = $this->app['eccube.plugin.dataimport.repository.dataimportstatus']->selectOrderIdsWithFixedByCustomer(
            $Customer->getId()
        );
        $this->actual = $this->app['eccube.plugin.dataimport.repository.dataimport']->calcCurrentDataImport(
            $Customer->getId(),
            $orderIds
        );

        $this->expected = 0;
        $this->verify('保有データインポートは '.$this->actual);
    }

    public function testCalculateTotalDiscountOnChangeConditionsWithAmountPlus()
    {
        $Customer = $this->createCustomer();
        $Order = $this->createOrder($Customer);

        // データインポート利用以外のプラグインで、お支払い金額が 0 になった場合
        $totalAmount = $Order->getTotalPrice();
        $this->app['eccube.service.shopping']->setDiscount($Order, $totalAmount); // 支払い金額分を値引きする
        $this->app['orm.em']->flush();

        $calculater = $this->app['eccube.plugin.dataimport.calculate.helper.factory'];
        $calculater->addEntity('Order', $Order);
        $calculater->addEntity('Customer', $Customer);

        $this->expected = false;
        $this->actual = $calculater->calculateTotalDiscountOnChangeConditions();
        $this->verify('データインポート利用以外のプラグインで、お支払い金額が 0 になった場合は false');

        $this->expected = 0;
        $this->actual = $Order->getTotalPrice();
        $this->verify('お支払い金額は '.$this->actual.' 円');
    }

    public function testCalculateTotalDiscountOnChangeConditionsWithException()
    {
        try {
            $calculater = $this->app['eccube.plugin.dataimport.calculate.helper.factory'];
            $calculater->calculateTotalDiscountOnChangeConditions();
            $this->fail('Throwable to \LogicException');
        } catch (\LogicException $e) {
            $this->assertEquals('Order not found.', $e->getMessage());
        }

        $Customer = $this->createCustomer();
        $Order = $this->createOrder($Customer);
        try {
            $calculater = $this->app['eccube.plugin.dataimport.calculate.helper.factory'];
            $calculater->addEntity('Order', $Order);
            $calculater->calculateTotalDiscountOnChangeConditions();
            $this->fail('Throwable to \LogicException');
        } catch (\LogicException $e) {
            $this->assertEquals('Customer not found.', $e->getMessage());
        }
    }

    public function testCalculateTotalDiscountOnChangeConditionsWithDataImportInfoNotfound()
    {
        // DataImportInfo が削除される. イレギュラー.
        $DataImportInfos = $this->app['eccube.plugin.dataimport.repository.dataimportinfo']->findAll();
        foreach ($DataImportInfos as $DataImportInfo) {
            $this->app['orm.em']->remove($DataImportInfo);
            $this->app['orm.em']->flush($DataImportInfo);
        }
        $Customer = $this->createCustomer();
        $Order = $this->createOrder($Customer);

        try {
            $calculater = $this->app['eccube.plugin.dataimport.calculate.helper.factory'];
            $calculater->addEntity('Order', $Order);
            $calculater->addEntity('Customer', $Customer);
            $calculater->calculateTotalDiscountOnChangeConditions();
            $this->fail('Throwable to \LogicException');
        } catch (\LogicException $e) {
            $this->assertEquals('DataImportInfo not found.', $e->getMessage());
        }
    }

    public function testSetUseDataImport()
    {
        $calculater = $this->app['eccube.plugin.dataimport.calculate.helper.factory'];

        $this->expected = false;
        $this->actual = $calculater->setUseDataImport(-1);
        $this->verify();

        $this->expected = true;
        $this->actual = $calculater->setUseDataImport(0);
        $this->verify();

        $this->expected = true;
        $this->actual = $calculater->setUseDataImport(1);
        $this->verify();
    }

    public function testGetConversionDataImport()
    {
        $Customer = $this->createCustomer();
        $Order = $this->createOrder($Customer);

        $calculater = $this->app['eccube.plugin.dataimport.calculate.helper.factory'];
        $calculater->addEntity('Order', $Order);
        $calculater->addEntity('Customer', $Customer);
        $calculater->setUseDataImport(200);

        $this->expected = 200;
        $this->actual = $calculater->getConversionDataImport();
        $this->verify();
    }

    public function testSetDiscount()
    {
        $Customer = $this->createCustomer();
        $Order = $this->createOrder($Customer);
        $Order->setDiscount(90); // データインポート値引き10円 + その他値引き90円

        $calculater = $this->app['eccube.plugin.dataimport.calculate.helper.factory'];
        $calculater->addEntity('Order', $Order);
        $calculater->addEntity('Customer', $Customer);
        $calculater->setUseDataImport(10); // データインポート利用10pt

        $this->expected = true;
        $this->actual = $calculater->setDiscount(0);
        $this->verify('10pt 利用しているかどうか');

        $this->expected = 100;
        $this->actual = $Order->getDiscount();
        $this->verify('値引き額が正しいかどうか');
    }

    public function testSetDiscount2()
    {
        $Customer = $this->createCustomer();
        $Order = $this->createOrder($Customer);
        $Order->setDiscount(10); // その他値引き10円

        $calculater = $this->app['eccube.plugin.dataimport.calculate.helper.factory'];
        $calculater->addEntity('Order', $Order);
        $calculater->addEntity('Customer', $Customer);
        $calculater->setUseDataImport(90); // データインポート利用90pt

        $this->expected = true;
        $this->actual = $calculater->setDiscount(0);
        $this->verify('同一受注の前回利用データインポートは 0');

        $this->expected = 100;
        $this->actual = $Order->getDiscount();
        $this->verify('値引き額が正しいかどうか');
    }

    /**
     * 仮利用データインポートの履歴を含むテストケース
     */
    public function testSetDiscount3()
    {
        $previousUseDataImport = 100; // 前回入力したデータインポート100
        $useDataImport = 10;         // 今回利用データインポート10
        $otherDiscount = 5;     // その他の割引5円
        $Customer = $this->createCustomer();
        $Order = $this->createOrder($Customer);

         // その他値引き5円 + 前回入力したデータインポート値引き分100円
        $Order->setDiscount($otherDiscount + $previousUseDataImport);

        // 仮利用データインポートの履歴を作成する
        $this->app['eccube.plugin.dataimport.history.service']->addEntity($Order);
        $this->app['eccube.plugin.dataimport.history.service']->addEntity($Order->getCustomer());
        $this->app['eccube.plugin.dataimport.history.service']->savePreUseDataImport($previousUseDataImport * -1); // 前回入力したデータインポートを履歴に設定

        $lastPreUseDataImport = $this->app['eccube.plugin.dataimport.repository.dataimport']->getLatestPreUseDataImport($Order);
        $lastPreUseDataImport = abs($lastPreUseDataImport);

        $this->expected = $previousUseDataImport;
        $this->actual = $lastPreUseDataImport;
        $this->verify('前回入力したデータインポートは '.$this->expected.' pt');

        $calculater = $this->app['eccube.plugin.dataimport.calculate.helper.factory'];
        $calculater->addEntity('Order', $Order);
        $calculater->addEntity('Customer', $Customer);
        $calculater->setUseDataImport($useDataImport); // データインポート利用10pt

        $this->expected = true;
        $this->actual = $calculater->setDiscount($lastPreUseDataImport); // 同一受注でデータインポートを入力した履歴があるかどうか
        $this->verify('同一受注の利用データインポート履歴あり');

        $this->expected = $useDataImport + $otherDiscount;
        $this->actual = $Order->getDiscount();
        $this->verify('値引き額が正しいかどうか');
    }

    /**
     * discount に負の整数を入力するケース
     */
    public function testSetDiscount4()
    {
        $previousUseDataImport = 100; // 前回入力したデータインポート100
        $useDataImport = 100;         // 今回利用データインポート100
        $otherDiscount = -5;     // その他の割引-5円(5円加算)
        $Customer = $this->createCustomer();
        $Order = $this->createOrder($Customer);

         // その他値引き-5円 + 前回入力したデータインポート値引き分100円
        $Order->setDiscount($otherDiscount + $previousUseDataImport);

        // 仮利用データインポートの履歴を作成する
        $this->app['eccube.plugin.dataimport.history.service']->addEntity($Order);
        $this->app['eccube.plugin.dataimport.history.service']->addEntity($Order->getCustomer());
        $this->app['eccube.plugin.dataimport.history.service']->savePreUseDataImport($previousUseDataImport * -1); // 前回入力したデータインポートを履歴に設定

        $lastPreUseDataImport = $this->app['eccube.plugin.dataimport.repository.dataimport']->getLatestPreUseDataImport($Order);
        $lastPreUseDataImport = abs($lastPreUseDataImport);

        $this->expected = $previousUseDataImport;
        $this->actual = $lastPreUseDataImport;
        $this->verify('前回入力したデータインポートは '.$this->expected.' pt');

        $calculater = $this->app['eccube.plugin.dataimport.calculate.helper.factory'];
        $calculater->addEntity('Order', $Order);
        $calculater->addEntity('Customer', $Customer);
        $calculater->setUseDataImport($useDataImport); // データインポート利用100pt

        $this->expected = true;
        $this->actual = $calculater->setDiscount($lastPreUseDataImport); // 同一受注でデータインポートを入力した履歴があるかどうか
        $this->verify('同一受注の利用データインポート履歴あり');

        $this->expected = $useDataImport + $otherDiscount;
        $this->actual = $Order->getDiscount();
        $this->verify('値引き額が正しいかどうか');
    }

    /**
     * 仮利用データインポートの登録
     * @param Customer $customer
     * @param Order $order
     * @param int $dataimportValue
     * @return DataImport
     */
    private function createPreUseDataImport($DataImportInfo, $Customer, $Order, $dataimportValue = -10)
    {
        $DataImport = new DataImport();
        $DataImport
            ->setCustomer($Customer)
            ->setPlgDynamicDataImport($dataimportValue)
            ->setPlgDataImportType(DataImportHistoryHelper::STATE_PRE_USE)
            ->setDataImportInfo($DataImportInfo)
            ->setOrder($Order);

        $this->app['orm.em']->persist($DataImport);
        $this->app['orm.em']->flush();
        return $DataImport;
    }
}
