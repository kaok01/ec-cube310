<?php
/*
* This file is part of EC-CUBE
*
* Copyright(c) 2000-2015 LOCKON CO.,LTD. All Rights Reserved.
* http://www.lockon.co.jp/
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace Plugin\ShoppingEx;

use Eccube\Application;
use Eccube\Common\Constant;
use Eccube\Entity\Category;
use Eccube\Event\EventArgs;
use Eccube\Event\TemplateEvent;
use Plugin\ShoppingEx\Entity\ShoppingEx;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;

class ShoppingExtensionEvent
{

    /**
     * セッションに保存するテンポラリキー
     */
    const SHOPPINGEXT_SESSON_ORDER_KEY = 'eccube.plugin.downloadproduct.shoppingext.order.key';
    const SHOPPINGEXT_SESSION_REDIRECT_KEY = 'eccube.plugin.downloadproduct.shoppingext.redirect.key';
    const SHOPPINGEXT_SESSION_CUSTOMINFO_KEY = 'eccube.plugin.downloadproduct.shoppingext.custominfo.key';



    /**
     * @var \Eccube\Application
     */
    private $app;

    /**
     * ShoppingExEvent constructor.
     *
     * @param $app
     */
    public function __construct($app)
    {
        $this->app = $app;

    }

    /**
     * 商品一覧画面にカテゴリコンテンツを表示する.
     *
     * @param TemplateEvent $event
     */
    public function onRenderProductList(TemplateEvent $event)
    {

    /*
        $parameters = $event->getParameters();

        // カテゴリIDがない場合、レンダリングしない
        if (is_null($parameters['Category'])) {
            return;
        }

        // 登録がない、もしくは空で登録されている場合、レンダリングをしない
        $Category = $parameters['Category'];
        $ShoppingEx = $this->app['shoppingex.repository.shoppingex']
            ->find($Category->getId());
        if (is_null($ShoppingEx) || $ShoppingEx->getContent() == '') {
            return;
        }

        // twigコードにカテゴリコンテンツを挿入
        $snipet = '<div class="row">{{ ShoppingEx.content | raw }}</div>';
        $search = '<div id="result_info_box"';
        $replace = $snipet.$search;
        $source = str_replace($search, $replace, $event->getSource());
        $event->setSource($source);

        // twigパラメータにカテゴリコンテンツを追加
        $parameters['ShoppingEx'] = $ShoppingEx;
        $event->setParameters($parameters);
    */
    }

    private function onExecute(EventArgs $event){
        $app = $this->app;
        $req = $event->getRequest();
        $sec = $req->getSession();

        $Order = $event->getArgument('Order');




    }
    public function onFrontShoppingIndexInitialize(EventArgs $event){

        $this->onExecute($event);


    }

    public function onFrontShoppingConfirmInitialize(EventArgs $event){
        $app = $this->app;
        $this->onExecute($event);

        $builder = $event->getArgument('builder');
        $form = $builder->getForm();



    }
    public function onFrontShoppingConfirmProcessing(EventArgs $event){

        $app = $this->app;
        $Order = $event->getArgument('Order');


        $form = $event->getArgument('form');
        $dat = $form->GetData();




        $event->getRequest()
            ->getSession()
            ->set(
                self::SHOPPINGEXT_SESSION_CUSTOMINFO_KEY,
                $event->getArgument('custominfo')
                 );



        //$app['eccube.plugin.downloadproduct.shoppingext.service.shoppingext']->sendShoppingOrder($event);


    }

    public function onFrontShoppingConfirmComplete(EventArgs $event){
        $app = $this->app;
        //セッションから消す
        //$session->set(self::SHOPPINGEX_SESSION_REDIRECT_KEY,$request->request);
        $req = $event->getRequest();
        $sec = $req->getSession();
        if($sec->get(self::SHOPPINGEX_SESSION_REDIRECT_KEY)){
            $sec->remove(self::SHOPPINGEX_SESSION_REDIRECT_KEY);
        }
        if($sec->get(self::SHOPPINGEXT_SESSION_CUSTOMINFO_KEY)){
            $sec->remove(self::SHOPPINGEXT_SESSION_CUSTOMINFO_KEY);
        }




    }

    public function onFrontShoppingPaymentInitialize(EventArgs $event){
        $app = $this->app;

        $this->onExecute($event);

        $builder = $event->getArgument('builder');
        $form = $builder->getForm();

        $request = $event->getRequest();




    }

    public function onFrontShoppingPaymentComplete(EventArgs $event){



    }



    public function onFrontShoppingDeliveryInitialize(EventArgs $event){
        $app = $this->app;


    }
    public function onFrontShoppingDeliveryComplete(EventArgs $event){

    }
    public function onFrontShoppingShippingChangeInitialize(EventArgs $event){
        $app = $this->app;


    }
    public function onFrontShoppingShippingComplete(EventArgs $event){


    }
    public function onFrontShoppingShippingEditChangeInitialize(EventArgs $event){
        $app = $this->app;

        $this->onExecute($event);

        $id = $event->getArgument('id');

        $builder = $event->getArgument('builder');
        $form = $builder->getForm();




    }
    public function onFrontShoppingShippingEditInitialize(EventArgs $event){
        $app = $this->app;


    }
    public function onFrontShoppingShippingEditComplete(EventArgs $event){

    }
    public function onFrontShoppingShippingMultipleChangeInitialize(EventArgs $event){
        $app = $this->app;
        $this->onExecute($event);

        $builder = $event->getArgument('builder');
        $form = $builder->getForm();



    }
    public function onFrontShoppingShippingMultipleInitialize(EventArgs $event){
        $app = $this->app;

    }
    public function onFrontShoppingShippingMultipleComplete(EventArgs $event){

    }
    public function onFrontShoppingShippingMultipleEditInitialize(EventArgs $event){
        $app = $this->app;

    }
    public function onFrontShoppingShippingMultipleEditComplete(EventArgs $event){

    }

    /*
    メール文面に情報を差し込む処理
    プラグインの処理を上書き
    */
    public function onMailServiceMailOrder(EventArgs $event){


        $app = $this->app;
        
        $MailTemplate = $event['MailTemplate'];
        $Order = $event['Order'];
        $message = $event['message'];
        
        // $orderDetails = $Order->getOrderDetails();
        // $plgOrderDetails = $app['eccube.productoption.service.util']->getPlgOrderDetails($orderDetails);
        
        // $Shippings = $Order->getShippings();
        // $plgShipmentItems = $app['eccube.productoption.service.util']->getPlgShipmentItems($Shippings);
        

        $CardInfo = $app['request']
                        ->getSession()
                        ->get(
                            self::SHOPPINGEXT_SESSION_CUSTOMINFO_KEY
                             );
        // dump($CardInfo);
        if ($CardInfo){
            $CardInfo['cardno'] = '**** **** **** '.mb_substr($CardInfo['cardno'],-4);

        }

        $body = $app->renderView('Mail/order.twig', array(
            'header' => $MailTemplate->getHeader(),
            'footer' => $MailTemplate->getFooter(),
            'Order' => $Order,
            'Card' => $CardInfo,
            'plgOrderDetails' => $plgOrderDetails,
            'plgShipmentItems' => $plgShipmentItems,
        ));
        
        $message->setBody($body);
        
        $event['message'] = $message;

        $app['request']->getSession()
                       ->remove(self::SHOPPINGEXT_SESSION_CUSTOMINFO_KEY);

    }


    public function onFrontContactIndexComplete(EventArgs $event){
        $app = $this->app;

        // $app['eccube.plugin.shoppingex.service.shoppingex']->sendContact($event);


    }




}
