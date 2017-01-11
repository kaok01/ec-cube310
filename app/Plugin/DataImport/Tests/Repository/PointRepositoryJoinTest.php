<?php

namespace Eccube\Tests\Repository;

use Eccube\Application;
use Eccube\Entity\Customer;
use Eccube\Entity\Master\OrderStatus;
use Eccube\Entity\Order;
use Eccube\Event\EccubeEvents;
use Eccube\Event\EventArgs;
use Eccube\Tests\EccubeTestCase;
use Eccube\Tests\Web\AbstractWebTestCase;
use Eccube\Tests\Web\Admin\AbstractAdminWebTestCase;
use Plugin\DataImport\Entity\DataImportInfo;
use Plugin\DataImport\Entity\DataImportStatus;
use Plugin\DataImport\Helper\DataImportCalculateHelper\DataImportCalculateHelper;
use Symfony\Component\Form\Form;
use Symfony\Component\BrowserKit\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

/**
 * Class DataImportRepositoryScenarioTest
 *
 * @package Eccube\Tests\Repository
 */
class DataImportRepositoryJoinTest extends AbstractWebTestCase
{
    /**
     * @var int テストでの確定ステータス
     */
    private $dataimportFixStatus;

    public function setUp()
    {
        parent::setUp();
        $this->dataimportFixStatus  = (int)$this->app['config']['order_pre_end'];
    }

    // 購入（すぐに確定データインポート）
    public function testShoppingCompleteWithDataImportFix()
    {
        // データインポート設定を変更
        $this->updateDataImportSettings($this->app['config']['order_new']);

        // 注文する
        $customer = $this->createCustomer();
        $order = $this->DoOrder($customer);

        // 期待結果の計算
        $expectedDataImport = $this->CalcExpectedDataImport($order);

        // 検証
        $this->assertEquals($expectedDataImport, $this->getCurrentDataImport($customer));
        $this->assertEquals(0, $this->getProvisionalDataImport($customer));
    }

    // 購入（仮データインポート）
    public function testShoppingCompleteWithoutDataImportFix()
    {
        // データインポート設定を変更
        $this->updateDataImportSettings($this->dataimportFixStatus);

        // 注文する
        $customer = $this->createCustomer();
        $order = $this->DoOrder($customer);

        // 期待結果の計算
        $expectedDataImport = $this->CalcExpectedDataImport($order);

        // 検証
        $this->assertEquals(0, $this->getCurrentDataImport($customer));
        $this->assertEquals($expectedDataImport, $this->getProvisionalDataImport($customer));
    }

    // 受注変更（確定ステータスへの変更）
    public function testEditOrderToFixedStatus()
    {
        // データインポート設定を変更
        $this->updateDataImportSettings($this->dataimportFixStatus);

        // 注文する
        $customer = $this->createCustomer();
        $order = $this->DoOrder($customer);

        // 期待結果の計算
        $expectedDataImport = $this->CalcExpectedDataImport($order);

        // 検証（仮データインポートであること）
        $this->assertEquals(0, $this->getCurrentDataImport($customer));
        $this->assertEquals($expectedDataImport, $this->getProvisionalDataImport($customer));

        // データインポート確定する
        $this->ChangeOrderToFixStatus($this->dataimportFixStatus, $order, $customer);

        // 検証（確定していること）
        $this->assertEquals($expectedDataImport, $this->getCurrentDataImport($customer));
        $this->assertEquals(0, $this->getProvisionalDataImport($customer));
    }

    // 受注変更（未確定ステータスへの変更）
    public function testEditOrderToUnfixedStatus()
    {
        // データインポート設定を変更
        $this->updateDataImportSettings($this->dataimportFixStatus);

        // 注文する
        $customer = $this->createCustomer();
        $order = $this->DoOrder($customer);

        // 期待結果の計算
        $expectedDataImport = $this->CalcExpectedDataImport($order);

        // 検証（仮データインポートであること）
        $this->assertEquals(0, $this->getCurrentDataImport($customer));
        $this->assertEquals($expectedDataImport, $this->getProvisionalDataImport($customer));

        // データインポート確定する
        $unfixedStatus = (int)$this->app['config']['order_processing'];
        $this->ChangeOrderToFixStatus($unfixedStatus, $order, $customer);

        // 検証（仮データインポートのままであること）
        $this->assertEquals(0, $this->getCurrentDataImport($customer));
        $this->assertEquals($expectedDataImport, $this->getProvisionalDataImport($customer));
    }

    // 受注削除（確定データインポートを削除）
    public function testDeleteOrderWithFixedDataImport()
    {
        // データインポート設定を変更
        $this->updateDataImportSettings($this->app['config']['order_new']);

        // 注文する
        $customer = $this->createCustomer();
        $order = $this->DoOrder($customer);

        // 期待結果の計算
        $expectedDataImport = $this->CalcExpectedDataImport($order);

        // 検証（確定データインポートであること）
        $this->assertEquals($expectedDataImport, $this->getCurrentDataImport($customer));
        $this->assertEquals(0, $this->getProvisionalDataImport($customer));

        // 受注の削除
        $this->deleteOrder($order);

        // 検証（データインポート無くなっていること）
        $this->assertEquals(0, $this->getCurrentDataImport($customer));
        $this->assertEquals(0, $this->getProvisionalDataImport($customer));
    }

    // 受注削除（仮データインポートを削除）
    public function testDeleteOrderWithUnfixedDataImport()
    {
        // データインポート設定を変更
        $this->updateDataImportSettings($this->dataimportFixStatus);

        // 注文する
        $customer = $this->createCustomer();
        $order = $this->DoOrder($customer);

        // 期待結果の計算
        $expectedDataImport = $this->CalcExpectedDataImport($order);

        // 検証（仮データインポートであること）
        $this->assertEquals(0, $this->getCurrentDataImport($customer));
        $this->assertEquals($expectedDataImport, $this->getProvisionalDataImport($customer));

        // 受注の削除
        $this->deleteOrder($order);

        // 検証（データインポート無くなっていること）
        $this->assertEquals(0, $this->getCurrentDataImport($customer));
        $this->assertEquals(0, $this->getProvisionalDataImport($customer));
    }

    // 受注登録で受注作成
    public function testCreateOrderByOrderEditWithFixedStatus()
    {
        // データインポート設定を変更
        $this->updateDataImportSettings($this->app['config']['order_new']);
        
        // 受注情報を登録する
        $customer = $this->createCustomer();
        $order = $this->DoCreateNewOrder($customer);
        //$order = $this->DoOrder($customer);

        // 検証（データインポートステータスのレコードが作成されていること）
        // https://github.com/EC-CUBE/dataimport-plugin/issues/44
        $existedStatus = $this->app['eccube.plugin.dataimport.repository.dataimportstatus']->findOneBy(
            array('order_id' => $order->getId())
        );
        $this->assertEquals(1, $existedStatus->getStatus());
        $this->assertNotEmpty($existedStatus);
    }

    // 受注登録で受注作成（未確定ステータス）
    public function testCreateOrderByOrderEditWithUnfixedStatus()
    {
        // データインポート設定を変更
        $this->updateDataImportSettings($this->dataimportFixStatus);

        // 受注情報を登録する
        $customer = $this->createCustomer();
        $order = $this->DoCreateNewOrder($customer);

        // 検証（データインポートステータスのレコードが作成されていること）
        // https://github.com/EC-CUBE/dataimport-plugin/issues/44
        $existedStatus = $this->app['eccube.plugin.dataimport.repository.dataimportstatus']->findOneBy(
            array('order_id' => $order->getId())
        );
        $this->assertEquals(0, $existedStatus->getStatus());
        $this->assertNotEmpty($existedStatus);
    }

    /**
     * オーダーから加算データインポートを取得する
     * @param Order $order
     * @return int
     */
    private function CalcExpectedDataImport($order)
    {
        /** @var DataImportCalculateHelper $calculator */
        $calculator = $this->app['eccube.plugin.dataimport.calculate.helper.factory'];
        $calculator->addEntity('Order', $order);
        $expectedDataImport = $calculator->getAddDataImportByOrder();

        return $expectedDataImport;
    }

    /**
     * 仮データインポートの取得
     * @return int
     */
    private function getProvisionalDataImport($customer)
    {
        /** @var DataImportCalculateHelper $calculator */
        $calculator = $this->app['eccube.plugin.dataimport.calculate.helper.factory'];
        $calculator->addEntity('Customer', $customer);
        return $calculator->getProvisionalAddDataImport();
    }

    /**
     * 確定データインポートの取得
     * @param Customer $customer
     * @return int
     */
    private function getCurrentDataImport($customer)
    {
        $orderIds = $this->app['eccube.plugin.dataimport.repository.dataimportstatus']->selectOrderIdsWithFixedByCustomer(
            $customer->getId()
        );
        $currentDataImport = $this->app['eccube.plugin.dataimport.repository.dataimport']->calcCurrentDataImport(
            $customer->getId(),
            $orderIds
        );
        return $currentDataImport;
    }

    /**
     * データインポート設定の更新
     * @param int $status
     * @return DataImportInfo
     */
    private function updateDataImportSettings($status){
        $DataImportInfo = new DataImportInfo();
        $DataImportInfo
            ->setPlgAddDataImportStatus($status)
            ->setPlgBasicDataImportRate(1)
            ->setPlgCalculationType(1)
            ->setPlgDataImportConversionRate(1)
            ->setPlgRoundType(1);

        $this->app['orm.em']->persist($DataImportInfo);
        $this->app['orm.em']->flush();

        return $DataImportInfo;
    }

    /**
     * 注文をする
     * @return Order
     */
    private function DoOrder($customer)
    {
        $order = $this->createOrder($customer);

        // ログイン
        $this->logIn($customer);
        // 受注
        $event = new EventArgs(
            array(
                'Order' => $order,
            ),
            null
        );
        $this->app['eccube.event.dispatcher']->dispatch(EccubeEvents::SERVICE_SHOPPING_NOTIFY_COMPLETE, $event);

        return $order;
    }

    /**
     * 受注を受注登録から作成する
     * @return Order
     */
    private function DoCreateNewOrder($customer)
    {
        $order = $this->createOrder($customer);
        $order->getOrderStatus()->setId($this->app['config']['order_new']);

        // ログイン
        $this->logInAsAdmin($customer);

        // 初期化イベント
        $builder = $this->app['form.factory']->createBuilder('order', $order);
        $this->app['request'] = new Request();
        $event = new EventArgs(
            array(
                'builder' => $builder,
                'TargetOrder' => $order,
                'OriginOrder' => $order,
            ),
            null
        );
        $this->app['eccube.event.dispatcher']->dispatch(EccubeEvents::ADMIN_ORDER_EDIT_INDEX_INITIALIZE, $event);

        // 反映イベント
        $event = new EventArgs(
            array(
                'form' => $builder->getForm(),
                'TargetOrder' => $order,
                'Customer' => $customer,
            ),
            null
        );
        $this->app['eccube.event.dispatcher']->dispatch(EccubeEvents::ADMIN_ORDER_EDIT_INDEX_COMPLETE, $event);

        return $order;
    }

    /**
     * 受注をステータスを切り替える
     * @param int $status
     * @param Order $order
     * @param Customer $customer
     */
    private function ChangeOrderToFixStatus($status, $order, $customer)
    {
        // 初期化イベント
        $builder = $this->app['form.factory']->createBuilder('order', $order);
        $this->app['request'] = new Request();
        $event = new EventArgs(
            array(
                'builder' => $builder,
                'OriginOrder' => $order,
                'TargetOrder' => $order,
            ),
            null
        );
        $this->app['eccube.event.dispatcher']->dispatch(EccubeEvents::ADMIN_ORDER_EDIT_INDEX_INITIALIZE, $event);

        // ステータス変更
        $orderStatus = $this->app['eccube.repository.order_status']->find($status);
        $order->setOrderStatus($orderStatus);

        // 反映イベント
        $event = new EventArgs(
            array(
                'form' => $builder->getForm(),
                'OriginOrder' => $order,
                'TargetOrder' => $order,
                'Customer' => $customer,
            ),
            null
        );
        $this->app['eccube.event.dispatcher']->dispatch(EccubeEvents::ADMIN_ORDER_EDIT_INDEX_COMPLETE, $event);
    }

    /**
     * 受注を削除する
     * @param Order $order
     */
    private function deleteOrder($order)
    {
        $this->logInAsAdmin();
        $this->client->request(
            'DELETE',
            $this->app->path('admin_order_delete', array('id' => $order->getId()))
        );
    }

    /**
     * 管理者としてログインする
     * @param null $user
     * @return null
     */
    private function logInAsAdmin($user = null)
    {
        $firewall = 'admin';

        if (!is_object($user)) {
            $user = $this->app['eccube.repository.member']
                ->findOneBy(array(
                    'login_id' => 'admin',
                ));
        }

        $token = new UsernamePasswordToken($user, null, $firewall, array('ROLE_ADMIN'));

        $this->app['session']->set('_security_' . $firewall, serialize($token));
        $this->app['session']->save();

        $cookie = new Cookie($this->app['session']->getName(), $this->app['session']->getId());
        $this->client->getCookieJar()->set($cookie);
        return $user;
    }



}
