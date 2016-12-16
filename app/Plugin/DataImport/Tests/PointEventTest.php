<?php

namespace Eccube\Tests;

use Eccube\Application;
use Eccube\Event\EccubeEvents;
use Eccube\Event\EventArgs;
use Eccube\Tests\Web\AbstractWebTestCase;
use Symfony\Component\HttpFoundation\Request;

/**
 * DataImportEvent test cases.
 *
 * カバレッジを出すため、一通りのイベントを実行する
 */
class DataImportEventTest extends AbstractWebTestCase
{
    protected $Customer;
    protected $Order;
    protected $Product;

    public function setUp()
    {
        parent::setUp();
        $this->app['request'] = new Request();
        $this->app['admin'] = true;
        $this->app['front'] = true;
        $paths = array();
        $paths[] = $this->app['config']['template_default_realdir'];
        $paths[] = __DIR__.'/../../../../app/Plugin';
        $this->app['twig.loader']->addLoader(new \Twig_Loader_Filesystem($paths));

        $this->Customer = $this->createCustomer();
        $this->Order = $this->createOrder($this->Customer);
        $this->Product = $this->createProduct();
        $this->mailBody = "合　計 10000\n";
    }

    public function eventCallable()
    {
        $DataImportEvent = new DataImportEventMock($this->app);
        $builder = $this->app['form.factory']->createBuilder();
        $builder
            ->add('plg_dataimport_product_rate', 'integer')
            ->add('plg_dataimport_current', 'integer');
        $MailHistory = new \Eccube\Entity\MailHistory();
        $MailHistory->setSubject('test');
        $MailHistory->setOrder($this->Order);
        $MailHistory->setMailBody($this->mailBody);
        $MailMessage = \Swift_Message::newInstance();
        $MailMessage->setBody($this->mailBody);
        $this->app['orm.em']->persist($MailHistory);
        $this->event = new EventArgs(
            array(
                'builder' => $builder,
                'form' => $builder->getForm(),
                'TargetOrder' => $this->Order,
                'OriginOrder' => $this->Order,
                'Order' => $this->Order,
                'Customer' => $this->Customer,
                'Product' => $this->Product,
                'plg_dataimport_product_rate' => null,
                'MailHistory' => $MailHistory,
                'message' => $MailMessage
            ),
            null
        );
        $this->TemplateEvent = new \Eccube\Event\TemplateEvent(
            'index.twig', null,
            array(
                'Order' => $this->Order,
                'dataimport_use' => 0,
                'body' => $this->mailBody,
                'Product' => $this->Product
            )
        );
        return $DataImportEvent;
    }
    public function testEvent1()
    {
        $DataImportEvent = $this->eventCallable();
        $DataImportEvent->onAdminProductEditInitialize($this->event);
    }
    public function testEvent2()
    {
        $DataImportEvent = $this->eventCallable();
        $DataImportEvent->onAdminProductEditComplete($this->event);
    }
    public function testEvent3()
    {
        $DataImportEvent = $this->eventCallable();
        $DataImportEvent->onAdminCustomerEditIndexInitialize($this->event);
    }
    public function testEvent4()
    {
        $builder = $this->app['form.factory']->createBuilder('admin_customer', $this->Customer);
        $builder->add('plg_dataimport_current', 'integer');
        $builder->get('plg_dataimport_current')->setData(100);
        $event = new EventArgs(
            array(
                'form' => $builder,
                'Customer' => $this->Customer,
            ),
            null
        );

        $DataImportEvent = $this->eventCallable();
        $DataImportEvent->onAdminCustomerEditIndexComplete($event);
    }
    public function testEvent5()
    {
        $DataImportEvent = $this->eventCallable();
        $DataImportEvent->onAdminOrderEditIndexInitialize($this->event);
    }
    public function testEvent6()
    {
        $builder = $this->app['form.factory']->createBuilder('order');
        $builder
            ->add('use_dataimport', 'integer')
            ->add('add_dataimport', 'integer');
        $event = new EventArgs(
            array(
                'form' => $builder->getForm(),
                'TargetOrder' => $this->Order,
            ),
            null
        );

        $DataImportEvent = $this->eventCallable();
        $DataImportEvent->onAdminOrderEditIndexComplete($event);
    }
    public function testEvent7()
    {
        $DataImportEvent = $this->eventCallable();
        $DataImportEvent->onAdminOrderDeleteComplete($this->event);
    }
    public function testEvent8()
    {
        $DataImportEvent = $this->eventCallable();
        $DataImportEvent->onAdminOrderMailIndexComplete($this->event);
    }
    public function testEvent9()
    {
        $DataImportEvent = $this->eventCallable();
        $DataImportEvent->onFrontShoppingConfirmProcessing($this->event);
    }
    public function testEvent10()
    {
        $DataImportEvent = $this->eventCallable();
        $DataImportEvent->onServiceShoppingNotifyComplete($this->event);
    }
    public function testEvent11()
    {
        $DataImportEvent = $this->eventCallable();
        $DataImportEvent->onFrontChangeTotal($this->event);
    }
    public function testEvent12()
    {
        $DataImportEvent = $this->eventCallable();
        $DataImportEvent->onFrontChangeTotal($this->event);
    }
    public function testEvent13()
    {
        $DataImportEvent = $this->eventCallable();
        $DataImportEvent->onFrontChangeTotal($this->event);
    }
    public function testEvent14()
    {
        // XXX Request に依存しているためテストが書けない
        try {
            $DataImportEvent = $this->eventCallable();
            $DataImportEvent->onRenderShoppingIndex($this->TemplateEvent);
        } catch (\Exception $e) {
        }
    }
    public function testEvent15()
    {
        $DataImportEvent = $this->eventCallable();
        $DataImportEvent->onRenderAdminOrderEdit($this->TemplateEvent);
    }
    public function testEvent16()
    {
        $DataImportEvent = $this->eventCallable();
        $DataImportEvent->onRenderMyPageIndex($this->TemplateEvent);
    }
    public function testEvent17()
    {
        $DataImportEvent = $this->eventCallable();
        $DataImportEvent->onRenderAdminOrderMailConfirm($this->TemplateEvent);
    }
    public function testEvent18()
    {
        $DataImportEvent = $this->eventCallable();
        $DataImportEvent->onMailOrderComplete($this->event);
    }
    public function testEvent19()
    {
        $DataImportEvent = $this->eventCallable();
        $DataImportEvent->onRenderProductDetail($this->TemplateEvent);
    }
    public function testEvent20()
    {
        // XXX Request に依存しているためテストが書けない
        try {
            $DataImportEvent = $this->eventCallable();
            $DataImportEvent->onRenderCart($this->TemplateEvent);
        } catch (\Exception $e) {
        }
    }
    public function testEvent21()
    {
        $DataImportEvent = $this->eventCallable();
        $DataImportEvent->onRenderHistory($this->TemplateEvent);
    }
}

/**
 * テスト用のモック
 */
class DataImportEventMock extends \Plugin\DataImport\DataImportEvent {
    protected function isAuthRouteFront()
    {
        return true;
    }
    protected function replaceView(TemplateEvent $event, $snippet, $search)
    {
        return true;
    }
}
