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
namespace Eccube\Tests\Repository;

use Eccube\Application;
use Eccube\Entity\Customer;
use Eccube\Entity\Order;
use Eccube\Tests\EccubeTestCase;
use Plugin\DataImport\Entity\DataImport;
use Plugin\DataImport\Entity\DataImportInfo;
use Plugin\DataImport\Helper\DataImportHistoryHelper\DataImportHistoryHelper;

/**
 * Class DataImportInfoRepositoryTest
 *
 * @package Eccube\Tests\Repository
 */
class DataImportRepositoryTest extends EccubeTestCase
{
    /**
     *  int テストで使用する加算データインポート
     */
    const POINT_VALUE = 37;
    /**
     *  int テストで使用する手動編集データインポート
     */
    const POINT_MANUAL_VALUE = 173;
    /**
     *  int テストで使用する利用データインポート
     */
    const POINT_USE_VALUE = -7;
    /**
     *  int テストで使用する仮利用データインポート
     */
    const POINT_PRE_USE_VALUE = -17;

    private  $dataimportInfo;

    public function setUp()
    {
        parent::setUp();

        $this->dataimportInfo = $this->createDataImportInfo();
    }

    public function testCalcCurrentDataImport()
    {
        $customer = $this->createCustomer();
        $orderIds = array();

        // 準備：加算データインポートの履歴追加
        $dataimport = $this->createAddDataImport($customer);
        $orderIds[] = $dataimport->getOrder()->getId();

        // 検証：現在データインポートの計算
        $sumDataImport = $this->app['eccube.plugin.dataimport.repository.dataimport']->calcCurrentDataImport(
            $dataimport->getCustomer()->getId(),
            $orderIds
        );
        $this->expected = self::POINT_VALUE;
        $this->actual = $sumDataImport;
        $this->verify();
    }

    public function testCalcCurrentDataImportWithMultiOrder()
    {
        $customer = $this->createCustomer();
        $orderIds = array();

        // 準備：加算データインポートの履歴追加
        $orderCount = 3;
        for ($i = 0; $i < $orderCount; $i++) {
            $DataImport = $this->createAddDataImport($customer);
            $orderIds[] = $DataImport->getOrder()->getId();
        }

        // 検証：現在データインポートの計算
        $sumDataImport = $this->app['eccube.plugin.dataimport.repository.dataimport']->calcCurrentDataImport(
            $customer->getId(),
            $orderIds
        );
        $this->expected = self::POINT_VALUE * $orderCount;
        $this->actual = $sumDataImport;
        $this->verify();
    }

    public function testCalcCurrentDataImportWithAddDataImportAndManualDataImport()
    {
        $customer = $this->createCustomer();
        $no_calc_customer = $this->createCustomer();
        $orderIds = array();

        // 準備：加算データインポート/保有データインポート手動変更の履歴追加
        $orderIds[] = $this->createAddDataImport($customer)->getOrder()->getId();
        $this->createManualDataImport($customer);

        // 準備：集計対象としない会員のデータを追加する
        $this->createAddDataImport($no_calc_customer);
        $this->createManualDataImport($no_calc_customer);

        // 検証：現在データインポートの計算
        $sumDataImport = $this->app['eccube.plugin.dataimport.repository.dataimport']->calcCurrentDataImport(
            $customer->getId(),
            $orderIds
        );
        $this->expected = self::POINT_VALUE + self::POINT_MANUAL_VALUE;
        $this->actual = $sumDataImport;
        $this->verify();
    }

    public function testCalcCurrentDataImportWithAddDataImportAndUseDataImport()
    {
        $customer = $this->createCustomer();
        $orderIds = array();

        // 準備：加算データインポート/利用データインポート手動変更の履歴追加
        $dataimport = $this->createAddDataImport($customer);
        $this->createUseDataImport($customer, $dataimport->getOrder());
        $orderIds[] = $dataimport->getOrder()->getId();

        // 検証：現在データインポートの計算
        $sumDataImport = $this->app['eccube.plugin.dataimport.repository.dataimport']->calcCurrentDataImport(
            $dataimport->getCustomer()->getId(),
            $orderIds
        );
        $this->expected = self::POINT_VALUE + self::POINT_USE_VALUE;
        $this->actual = $sumDataImport;
        $this->verify();
    }

    public function testCalcProvisionalAddDataImport()
    {
        $customer = $this->createCustomer();
        $orderIds = array();

        // 準備：加算データインポートの履歴追加
        $dataimport = $this->createAddDataImport($customer);
        $orderIds[] = $dataimport->getOrder()->getId();

        // 検証：現在の仮データインポートの計算
        $sumDataImport = $this->app['eccube.plugin.dataimport.repository.dataimport']->calcProvisionalAddDataImport(
            $orderIds
        );
        $this->expected = self::POINT_VALUE;
        $this->actual = $sumDataImport;
        $this->verify();
    }

    public function testCalcProvisionalAddDataImportWithMultiOrder()
    {
        $customer = $this->createCustomer();
        $orderIds = array();

        // 準備：加算データインポートの履歴追加
        $orderCount = 3;
        for ($i = 0; $i < $orderCount; $i++) {
            $DataImport = $this->createAddDataImport($customer);
            $orderIds[] = $DataImport->getOrder()->getId();
        }

        // 検証：現在の仮データインポートの計算
        $sumDataImport = $this->app['eccube.plugin.dataimport.repository.dataimport']->calcProvisionalAddDataImport(
            $orderIds
        );
        $this->expected = self::POINT_VALUE * $orderCount;
        $this->actual = $sumDataImport;
        $this->verify();
    }

    public function testCalcProvisionalAddDataImportWithAddDataImportAndManualDataImport()
    {
        $customer = $this->createCustomer();
        $no_calc_customer = $this->createCustomer();
        $orderIds = array();

        // 準備：加算データインポート/保有データインポート手動変更の履歴追加
        $orderIds[] = $this->createAddDataImport($customer)->getOrder()->getId();
        $this->createManualDataImport($customer);

        // 準備：集計対象としない会員のデータを追加する
        $this->createAddDataImport($no_calc_customer);
        $this->createManualDataImport($no_calc_customer);

        // 検証：現在の仮データインポートの計算
        $sumDataImport = $this->app['eccube.plugin.dataimport.repository.dataimport']->calcProvisionalAddDataImport(
            $orderIds
        );
        $this->expected = self::POINT_VALUE;
        $this->actual = $sumDataImport;
        $this->verify();
    }

    public function testCalcProvisionalAddDataImportWithAddDataImportAndUseDataImport()
    {
        $customer = $this->createCustomer();
        $orderIds = array();

        // 準備：加算データインポート/利用データインポート手動変更の履歴追加
        $dataimport = $this->createAddDataImport($customer);
        $this->createUseDataImport($customer, $dataimport->getOrder());
        $orderIds[] = $dataimport->getOrder()->getId();

        // 検証：現在の仮データインポートの計算
        $sumDataImport = $this->app['eccube.plugin.dataimport.repository.dataimport']->calcProvisionalAddDataImport(
            $orderIds
        );
        $this->expected = self::POINT_VALUE;
        $this->actual = $sumDataImport;
        $this->verify();
    }

    public function testCalcCurrentDataImportWithManualDataImportOnly()
    {
        $customer = $this->createCustomer();
        $orderIds = array();

        // 準備：保有データインポート手動変更の履歴のみを追加
        $this->createManualDataImport($customer);

        // 検証：現在の保有データインポートの計算
        $sumDataImport = $this->app['eccube.plugin.dataimport.repository.dataimport']->calcCurrentDataImport(
            $customer->getId(),
            $orderIds
        );
        $this->expected = self::POINT_MANUAL_VALUE;
        $this->actual = $sumDataImport;
        $this->verify();
    }

    public function testGetLatestAddDataImportByOrder()
    {
        $customer = $this->createCustomer();
        $order = $this->createOrder($customer);

        $latestValue = 123;

        // 準備：加算データインポート/利用データインポート手動変更の履歴追加
        $this->createAddDataImport($customer, $order);   // dummy
        $this->createUseDataImport($customer, $order);   // dummy
        $this->createManualDataImport($customer);        // dummy
        $this->createAddDataImport($customer, $order, $latestValue); // これが期待する値
        $this->createUseDataImport($customer, $order);   // dummy
        $this->createManualDataImport($customer);        // dummy
        $this->createPreUseDataImport($customer, $order);   // dummy

        // 検証：最後に追加した加算データインポートの取得
        $value = $this->app['eccube.plugin.dataimport.repository.dataimport']->getLatestAddDataImportByOrder(
            $order
        );
        $this->expected = $latestValue;
        $this->actual = $value;
        $this->verify();
    }

    public function testGetLatestUseDataImport()
    {
        $customer = $this->createCustomer();
        $order = $this->createOrder($customer);

        $latestUseValue = 123;

        // 準備：加算データインポート/利用データインポート手動変更の履歴追加
        $this->createAddDataImport($customer, $order);   // dummy
        $this->createUseDataImport($customer, $order);   // dummy
        $this->createManualDataImport($customer);        // dummy
        $this->createUseDataImport($customer, $order, $latestUseValue);   // これが期待する値
        $this->createAddDataImport($customer, $order);   // dummy
        $this->createManualDataImport($customer);        // dummy
        $this->createPreUseDataImport($customer, $order);   // dummy

        // 検証：最後に追加した加算データインポートの取得
        $value = $this->app['eccube.plugin.dataimport.repository.dataimport']->getLatestUseDataImport(
            $order
        );
        $this->expected = $latestUseValue;
        $this->actual = $value;
        $this->verify();
    }

    public function testGetLatestPreUseDataImport()
    {
        $customer = $this->createCustomer();
        $order = $this->createOrder($customer);

        $latestPreUseValue = -123;

        // 準備：加算データインポート/利用データインポート手動変更の履歴追加
        $this->createAddDataImport($customer, $order);   // dummy
        $this->createUseDataImport($customer, $order);   // dummy
        $this->createPreUseDataImport($customer, $order);   // dummy
        $this->createManualDataImport($customer);        // dummy
        $this->createPreUseDataImport($customer, $order, $latestPreUseValue);   // これが期待する値
        $this->createUseDataImport($customer, $order);   // dummy
        $this->createAddDataImport($customer, $order);   // dummy
        $this->createManualDataImport($customer);        // dummy

        // 検証：最後に追加した加算データインポートの取得
        $value = $this->app['eccube.plugin.dataimport.repository.dataimport']->getLatestPreUseDataImport(
            $order
        );
        $this->expected = $latestPreUseValue;
        $this->actual = $value;
        $this->verify();
    }

    /**
     * 保有データインポート集計、および未確定の加算データインポート集計で、仮利用データインポートが除外できているかどうかを確認する
     *
     * https://github.com/EC-CUBE/dataimport-plugin/issues/108
     */
    public function testCalcDataImportWithPreUseDataImport()
    {
        $customer = $this->createCustomer();
        $order = $this->createOrder($customer);

        // 準備：加算データインポート/利用データインポート手動変更の履歴追加
        $this->createManualDataImport($customer, 1000);
        $this->createPreUseDataImport($customer, $order, -50);
        $this->createUseDataImport($customer, $order, -50);
        $this->createAddDataImport($customer, $order, 50);

        // 検証：保有データインポート集計で、仮利用データインポートは集計対象がら除外される
        $value = $this->app['eccube.plugin.dataimport.repository.dataimport']->calcCurrentDataImport(
            $customer->getId(),
            array($order->getId())
        );

        $this->expected = 1000;
        $this->actual = $value;
        $this->verify();

        // 検証：未確定の加算データインポート集計で、仮利用データインポートは集計対象がら除外される
        $value = $this->app['eccube.plugin.dataimport.repository.dataimport']->calcProvisionalAddDataImport(
            array($order->getId())
        );

        $this->expected = 50;
        $this->actual = $value;
        $this->verify();
    }

    /**
     * 加算データインポートの登録
     * @param Customer $customer
     * @param Order $order
     * @param int $dataimportValue
     * @return DataImport
     */
    private function createAddDataImport($customer, $order = null, $dataimportValue = self::POINT_VALUE)
    {
        if (empty($order)) {
            $order = $this->createOrder($customer);
        }

        $DataImport = new DataImport();
        $DataImport
            ->setCustomer($customer)
            ->setPlgDynamicDataImport($dataimportValue)
            ->setPlgDataImportType(DataImportHistoryHelper::STATE_ADD)
            ->setDataImportInfo($this->dataimportInfo)
            ->setOrder($order);

        $this->app['orm.em']->persist($DataImport);
        $this->app['orm.em']->flush();
        return $DataImport;
    }

    // DataImportoInfoの作成
    private function createDataImportInfo(){
        $DataImportInfo = new DataImportInfo();
        $DataImportInfo
            ->setPlgAddDataImportStatus(1)
            ->setPlgBasicDataImportRate(1)
            ->setPlgCalculationType(1)
            ->setPlgDataImportConversionRate(1)
            ->setPlgRoundType(1);

        $this->app['orm.em']->persist($DataImportInfo);
        $this->app['orm.em']->flush();

        return $DataImportInfo;
    }

    /**
     * 手動編集データインポートの登録
     * @param Customer $customer
     * @return DataImport
     */
    private function createManualDataImport($customer, $dataimportValue = self::POINT_MANUAL_VALUE)
    {
        $DataImport = new DataImport();
        $DataImport
            ->setCustomer($customer)
            ->setPlgDynamicDataImport($dataimportValue)
            ->setPlgDataImportType(DataImportHistoryHelper::STATE_CURRENT)
            ->setDataImportInfo($this->dataimportInfo)
            ->setOrder(null);

        $this->app['orm.em']->persist($DataImport);
        $this->app['orm.em']->flush();
        return $DataImport;
    }

    /**
     * 利用データインポートの登録
     * @param Customer $customer
     * @param Order $order
     * @param int $dataimportValue
     * @return DataImport
     */
    private function createUseDataImport($customer, $order, $dataimportValue = self::POINT_USE_VALUE)
    {
        $DataImport = new DataImport();
        $DataImport
            ->setCustomer($customer)
            ->setPlgDynamicDataImport($dataimportValue)
            ->setPlgDataImportType(DataImportHistoryHelper::STATE_USE)
            ->setDataImportInfo($this->dataimportInfo)
            ->setOrder($order);

        $this->app['orm.em']->persist($DataImport);
        $this->app['orm.em']->flush();
        return $DataImport;
    }

    /**
     * 仮利用データインポートの登録
     * @param Customer $customer
     * @param Order $order
     * @param int $dataimportValue
     * @return DataImport
     */
    private function createPreUseDataImport($customer, $order, $dataimportValue = self::POINT_PRE_USE_VALUE)
    {
        $DataImport = new DataImport();
        $DataImport
            ->setCustomer($customer)
            ->setPlgDynamicDataImport($dataimportValue)
            ->setPlgDataImportType(DataImportHistoryHelper::STATE_PRE_USE)
            ->setDataImportInfo($this->dataimportInfo)
            ->setOrder($order);

        $this->app['orm.em']->persist($DataImport);
        $this->app['orm.em']->flush();
        return $DataImport;
    }
}

