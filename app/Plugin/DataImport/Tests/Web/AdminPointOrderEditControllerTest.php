<?php

namespace Eccube\Tests\Web;

use Eccube\Tests\Web\Admin\AbstractAdminWebTestCase;
use Plugin\DataImport\Tests\Util\DataImportTestUtil;
use Plugin\DataImport\Entity\DataImportInfo;

class AdminDataImportOrderEditControllerTest extends AbstractAdminWebTestCase
{
    protected $Customer;
    protected $Order;
    protected $Product;

    public function setUp()
    {
        parent::setUp();
        $this->Customer = $this->createCustomer();
        $this->Product = $this->createProduct();
    }

    public function createFormData($Customer, $Product, $useDataImport = 0, $addDataImport = 0)
    {
        $ProductClasses = $Product->getProductClasses();
        $faker = $this->getFaker();
        $tel = explode('-', $faker->phoneNumber);

        $email = $faker->safeEmail;
        $delivery_date = $faker->dateTimeBetween('now', '+ 5 days');

        $order = array(
            '_token' => 'dummy',
            'Customer' => $Customer->getId(),
            'OrderStatus' => 1,
            'name' => array(
                'name01' => $faker->lastName,
                'name02' => $faker->firstName,
            ),
            'kana' => array(
                'kana01' => $faker->lastKanaName ,
                'kana02' => $faker->firstKanaName,
            ),
            'company_name' => $faker->company,
            'zip' => array(
                'zip01' => $faker->postcode1(),
                'zip02' => $faker->postcode2(),
            ),
            'address' => array(
                'pref' => '5',
                'addr01' => $faker->city,
                'addr02' => $faker->streetAddress,
            ),
            'tel' => array(
                'tel01' => $tel[0],
                'tel02' => $tel[1],
                'tel03' => $tel[2],
            ),
            'fax' => array(
                'fax01' => $tel[0],
                'fax02' => $tel[1],
                'fax03' => $tel[2],
            ),
            'email' => $email,
            'message' => $faker->text,
            'Payment' => 1,
            'discount' => 0,
            'delivery_fee_total' => 0,
            'charge' => 0,
            'note' => $faker->text,
            'use_dataimport' => $useDataImport,
            'add_dataimport' => $addDataImport,
            'OrderDetails' => array(
                array(
                    'Product' => $Product->getId(),
                    'ProductClass' => $ProductClasses[0]->getId(),
                    'price' => $ProductClasses[0]->getPrice02(),
                    'quantity' => 1,
                    'tax_rate' => 8
                )
            ),
            'Shippings' => array(
                array(
                    'name' => array(
                        'name01' => $faker->lastName,
                        'name02' => $faker->firstName,
                    ),
                    'kana' => array(
                        'kana01' => $faker->lastKanaName ,
                        'kana02' => $faker->firstKanaName,
                    ),
                    'company_name' => $faker->company,
                    'zip' => array(
                        'zip01' => $faker->postcode1(),
                        'zip02' => $faker->postcode2(),
                    ),
                    'address' => array(
                        'pref' => '5',
                        'addr01' => $faker->city,
                        'addr02' => $faker->streetAddress,
                    ),
                    'tel' => array(
                        'tel01' => $tel[0],
                        'tel02' => $tel[1],
                        'tel03' => $tel[2],
                    ),
                    'fax' => array(
                        'fax01' => $tel[0],
                        'fax02' => $tel[1],
                        'fax03' => $tel[2],
                    ),
                    'Delivery' => 1,
                    'DeliveryTime' => 1,
                    'shipping_delivery_date' => array(
                        'year' => $delivery_date->format('Y'),
                        'month' => $delivery_date->format('n'),
                        'day' => $delivery_date->format('j')
                    )
                )
            )
        );
        return $order;
    }

    public function testRoutingAdminOrderNewPost()
    {
        $currentDataImport = 1000;   // 現在の保有データインポート
        $useDataImport = 100;        // 使用するデータインポート

        DataImportTestUtil::saveCustomerDataImport($this->Customer, $currentDataImport, $this->app);

        $crawler = $this->client->request(
            'POST',
            $this->app->url('admin_order_new'),
            array(
                'order' => $this->createFormData($this->Customer, $this->Product, $useDataImport),
                'mode' => 'register'
            )
        );

        $url = $crawler->filter('a')->text();
        $this->assertTrue($this->client->getResponse()->isRedirect($url));

        preg_match('/([0-9]+)/', $url, $match);
        $order_id = $match[0];

        $crawler = $this->client->request(
            'GET',
            $this->app->url('admin_order_edit', array('id' => $order_id))
        );

        $this->expected = number_format($currentDataImport - $useDataImport).' Pt';
        $this->actual = $crawler->filter('#dataimport_info_box p')->text();
        $this->verify('受注管理画面に表示されるデータインポートは '.$this->expected);

        $this->expected = $currentDataImport - $useDataImport;
        $this->actual = DataImportTestUtil::calculateCurrentDataImport($this->Customer, $this->app);
        $this->verify('現在の保有データインポートは '.$this->expected);
    }

    public function testRoutingAdminOrderNewPostUseAndAddIsZero()
    {
        $currentDataImport = 1000;   // 現在の保有データインポート
        $useDataImport = 0;        // 使用するデータインポート
        $addDataImport = 0;        // 加算するデータインポート

        DataImportTestUtil::saveCustomerDataImport($this->Customer, $currentDataImport, $this->app);

        $crawler = $this->client->request(
            'POST',
            $this->app->url('admin_order_new'),
            array(
                'order' => $this->createFormData($this->Customer, $this->Product, $useDataImport, $addDataImport),
                'mode' => 'register'
            )
        );

        $url = $crawler->filter('a')->text();
        $this->assertTrue($this->client->getResponse()->isRedirect($url));

        preg_match('/([0-9]+)/', $url, $match);
        $order_id = $match[0];

        $crawler = $this->client->request(
            'GET',
            $this->app->url('admin_order_edit', array('id' => $order_id))
        );

        $this->expected = number_format($currentDataImport - $useDataImport).' Pt';
        $this->actual = $crawler->filter('#dataimport_info_box p')->text();
        $this->verify('受注管理画面に表示されるデータインポートは '.$this->expected);

        $this->expected = $currentDataImport - $useDataImport;
        $this->actual = DataImportTestUtil::calculateCurrentDataImport($this->Customer, $this->app);
        $this->verify('現在の保有データインポートは '.$this->expected);
    }

    public function testRoutingAdminOrderNewPostUseAndAddIsNull()
    {
        $currentDataImport = 1000;   // 現在の保有データインポート
        $useDataImport = null;        // 使用するデータインポート
        $addDataImport = null;        // 加算するデータインポート

        DataImportTestUtil::saveCustomerDataImport($this->Customer, $currentDataImport, $this->app);

        $crawler = $this->client->request(
            'POST',
            $this->app->url('admin_order_new'),
            array(
                'order' => $this->createFormData($this->Customer, $this->Product, $useDataImport, $addDataImport),
                'mode' => 'register'
            )
        );

        $url = $crawler->filter('a')->text();
        $this->assertTrue($this->client->getResponse()->isRedirect($url));

        preg_match('/([0-9]+)/', $url, $match);
        $order_id = $match[0];

        $crawler = $this->client->request(
            'GET',
            $this->app->url('admin_order_edit', array('id' => $order_id))
        );

        $this->expected = number_format($currentDataImport - $useDataImport).' Pt';
        $this->actual = $crawler->filter('#dataimport_info_box p')->text();
        $this->verify('受注管理画面に表示されるデータインポートは '.$this->expected);

        $this->expected = $currentDataImport - $useDataImport;
        $this->actual = DataImportTestUtil::calculateCurrentDataImport($this->Customer, $this->app);
        $this->verify('現在の保有データインポートは '.$this->expected);
    }

    public function testRoutingAdminOrderNewPostUseAndAddIsOne()
    {
        // データインポート確定ステータスを「発送済み」に設定
        $DataImportInfo = $this->app['eccube.plugin.dataimport.repository.dataimportinfo']->getLastInsertData();
        $DataImportInfo->setPlgAddDataImportStatus($this->app['config']['order_deliv']);
        $this->app['orm.em']->flush();

        $currentDataImport = 1000;   // 現在の保有データインポート
        $useDataImport = 1;        // 使用するデータインポート
        $addDataImport = 1;        // 加算するデータインポート

        DataImportTestUtil::saveCustomerDataImport($this->Customer, $currentDataImport, $this->app);

        $crawler = $this->client->request(
            'POST',
            $this->app->url('admin_order_new'),
            array(
                'order' => $this->createFormData($this->Customer, $this->Product, $useDataImport, $addDataImport),
                'mode' => 'register'
            )
        );

        $url = $crawler->filter('a')->text();
        $this->assertTrue($this->client->getResponse()->isRedirect($url));

        preg_match('/([0-9]+)/', $url, $match);
        $order_id = $match[0];

        $crawler = $this->client->request(
            'GET',
            $this->app->url('admin_order_edit', array('id' => $order_id))
        );

        $this->expected = number_format($currentDataImport - $useDataImport).' Pt';
        $this->actual = $crawler->filter('#dataimport_info_box p')->text();
        $this->verify('受注管理画面に表示されるデータインポートは '.$this->expected);

        $this->expected = $currentDataImport - $useDataImport;
        $this->actual = DataImportTestUtil::calculateCurrentDataImport($this->Customer, $this->app);
        $this->verify('現在の保有データインポートは '.$this->expected);
    }

    public function testRoutingAdminOrderEdit()
    {
        $currentDataImport = 1000;
        $Customer = $this->createCustomer();
        $Order = $this->createOrder($Customer);

        DataImportTestUtil::saveCustomerDataImport($Customer, $currentDataImport, $this->app);

        $formData = $this->createFormData($Customer, $this->Product);
        $crawler = $this->client->request(
            'GET',
            $this->app->url('admin_order_edit', array('id' => $Order->getId()))
        );

        $this->expected = number_format($currentDataImport).' Pt';
        $this->actual = $crawler->filter('#dataimport_info_box p')->text();
        $this->verify('受注管理画面に表示されるデータインポートは '.$this->expected);
    }

    public function testRoutingAdminOrderEditPost()
    {
        $currentDataImport = 1000;   // 現在の保有データインポート
        $useDataImport = 100;        // 使用するデータインポート

        $Customer = $this->createCustomer();
        $Order = $this->createOrder($Customer);
        DataImportTestUtil::saveCustomerDataImport($Customer, $currentDataImport, $this->app);

        $formData = $this->createFormData($Customer, $this->Product, $useDataImport);
        $crawler = $this->client->request(
            'POST',
            $this->app->url('admin_order_edit', array('id' => $Order->getId())),
            array(
                'order' => $formData,
                'mode' => 'register'
            )
        );
        $this->assertTrue($this->client->getResponse()->isRedirect($this->app->url('admin_order_edit', array('id' => $Order->getId()))));

        $crawler = $this->client->request(
            'GET',
            $this->app->url('admin_order_edit', array('id' => $Order->getId()))
        );

        $this->expected = number_format($currentDataImport - $useDataImport).' Pt';
        $this->actual = $crawler->filter('#dataimport_info_box p')->text();
        $this->verify('受注管理画面に表示されるデータインポートは '.$this->expected);

        $this->expected = $currentDataImport - $useDataImport;
        $this->actual = DataImportTestUtil::calculateCurrentDataImport($Customer, $this->app);
        $this->verify('現在の保有データインポートは '.$this->expected);
    }
}
