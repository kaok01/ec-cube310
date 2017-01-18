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

class ShoppingExEvent
{
    /**
     * プラグインが追加するフォーム名
     */
    const SHOPPINGEX_TEXTAREA_NAME = 'cardno';

    /**
     * セッションに保存するテンポラリキー
     */
    const SHOPPINGEX_SESSON_ORDER_KEY = 'eccube.plugin.shoppingex.order.key';
    const SHOPPINGEX_SESSION_KEY = 'eccube.plugin.shoppingex.cardinfovalue.key';
    const SHOPPINGEX_SESSION_REDIRECT_KEY = 'eccube.plugin.shoppingex.redirect.key';

    const SHOPPINGEX_CREDIT_ORDER_TYPE_ID = "5";    //クレカ
    const SHOPPINGEX_SELFPAY_ORDER_TYPE_ID = "4";   //代引き

    const SHOPPINGEX_PAYMONTHLY_PRODUCTCLASS_ID = "9";   //月額払い
    const SHOPPINGEX_PAYONCE_PRODUCTCLASS_ID = "10";   //一括払い


    /**
     * @var \Eccube\Application
     */
    private $app;
    private $hasPayMonthly = false;
    private $SHOPPINGEX_DELIVERY_FIX_FEE = null;

    /**
     * ShoppingExEvent constructor.
     *
     * @param $app
     */
    public function __construct($app)
    {
        $this->app = $app;
        if (isset($app['config']['shoppingex_delivery_fix_fee'])){
            $this->SHOPPINGEX_DELIVERY_FIX_FEE = $app['config']['shoppingex_delivery_fix_fee'];

        }
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
    private function setCustomDeliveryFee($Order,$total_recalc = false){

        $app = $this->app;
        $deli = $Order->getDeliveryFeeTotal();
        $taxservice = $app['eccube.service.tax_rule'];
        //５００円固定
        if($this->SHOPPINGEX_DELIVERY_FIX_FEE){
            $delifeeIncTax = $taxservice->getPriceIncTax($this->SHOPPINGEX_DELIVERY_FIX_FEE);

            $Order->setDeliveryFeeTotal($delifeeIncTax);
            //５００円固定

            $total = $Order->getTotal();
            //５００円固定

            if($total_recalc){
                $Order->setTotal($total - $deli + $delifeeIncTax);
                //５００円固定
               
            }
        }

    }
    private function onExecute(EventArgs $event){
        $app = $this->app;
        $req = $event->getRequest();
        $sec = $req->getSession();

        $Order = $event->getArgument('Order');
        $this->setCustomDeliveryFee($Order,true);

        $OrderMaker = array();
        $hasSimOrder = false;
        $hasSimCount = 0;
        $hasExcludeSimMaker = false;
        $excludepayments = array();
        $excludemonthly = array();

        $eppsrv = $app['eccube.plugin.service.epp.util'];

        foreach($Order->getOrderDetails() as $od){
            $excludepayment = $eppsrv->getExcludePaymentSetting($od->getProduct()->getId());

            //$excludepayment = $app['config']['shoppingex_exclude_payment']['products'][$od->getProduct()->getId()];
            if($od->getProductClass()->getClassCategory2()->getId()
                !=self::SHOPPINGEX_PAYONCE_PRODUCTCLASS_ID){
                //月額払い扱いを除外する場合
                if($excludepayment['excludemonthly']){
                    $excludemonthly[$od->getProduct()->getId()] = true;
                }else{

                    $this->hasPayMonthly = true;

                }
            }

            if($od->getProduct()->getId()){
                $makerproduct = $app['eccube.plugin.maker.repository.product_maker']
                                    ->find($od->getProduct()->getId());
                if($makerproduct){
                    $OrderMaker[]=$makerproduct->getMaker()->getId();
                }
            }

            if($od->getProductClass()->getProductType()->getId()
                ==$app['config']['producttype_ex_sim_type']){
                $hasSimOrder = true;
                $hasSimCount++;

                //SIMのメーカをチェックする
                if($makerproduct){
                    //表示除外メーカを含む場合、
                    if (in_array(
                        $makerproduct->getMaker()->getId(),
                        explode(',',$app['config']['shoppingex_exclude_sim_maker'])
                        )){
                        $hasExcludeSimMaker = true;
                    }



                }
            }

            foreach($excludepayment['target'] as $ep){
                if($ep){
                    $excludepayments[$ep]=true;
                }

            }


        }
        //注記除外メーカのみの場合、表示をはずす
        if($hasExcludeSimMaker && $hasSimOrder && $hasSimCount == 1){
            $hasSimOrder = false;
        }



        //$Order = $app['eccube.productoption.service.shopping']->customOrder($Order);
        $paymentid = $Order->getPayment()->getId();
        $currpayment = $event->getRequest()->get('shopping')['payment'];
        if(empty($currpayment)){
            $currpayment = $paymentid;
        }
        $builder = $event->getArgument('builder');
        // dump($builder->get('payment')->GetData());
        //postされなくなるのでコメント
        //$builder->get('payment')->setDisabled($this->hasPayMonthly);
        if($this->hasPayMonthly){

            foreach($builder->get('payment') as $g){

                if($g->getName()==self::SHOPPINGEX_SELFPAY_ORDER_TYPE_ID){

                    $builder->get('payment')->remove(self::SHOPPINGEX_SELFPAY_ORDER_TYPE_ID);
                }

            }

        }

        //除外する支払方法
        if(count($excludepayments)>0){
            $temppayment = null;
            $py = $builder->get('payment');
            foreach($builder->get('payment') as $g){
                if($excludepayments[$g->getName()]){
                    $builder->get('payment')->remove($g->getName());


                }else{
                    $currpayment =  $g->getName();   
                    //$g->setData(true);
                    //$g->setChecked(true);
                }

            }
            $pydata = $builder->get('payment')->getAttributes()['choice_list_view']->choices[$currpayment]->data;

            $builder->get('payment')->setData($pydata);
            $Order->setPayment($pydata);
            $Order->setPaymentMethod($pydata->getMethod());
        }

        $sec->set(self::SHOPPINGEX_SESSON_ORDER_KEY,array(
            'hasPayMonthly'=>$this->hasPayMonthly,
            'hasSimOrder'=>$hasSimOrder,
            'Order'=>$Order,
            'OrderMaker'=>$OrderMaker
            ));

        $event->setArgument('Order',$Order);


        //クレカ決済を選択した場合
        if($currpayment==5){
            $ShoppingEx = $app['shoppingex.repository.shoppingex']->find($Order->getId());
            if(is_null($ShoppingEx)){
                $ShoppingEx = new ShoppingEx();

            }


            $builder->add(
                        self::SHOPPINGEX_TEXTAREA_NAME,
                        'cardno',
                            array(
                            'class' => 'Plugin\ShoppingEx\Entity\ShoppingEx',
                            'property' => 'method',
                            'data' => $ShoppingEx,
                            )
                    )
            ;

            if($sec->get(self::SHOPPINGEX_SESSION_REDIRECT_KEY)){
                $form  = $builder->getForm();
                $reqbulkdata = $sec->get(self::SHOPPINGEX_SESSION_REDIRECT_KEY)->get('shopping');
                if(isset($reqbulkdata['cardno'])){

                    // 初期値を設定
                    $fms = $builder->get(self::SHOPPINGEX_TEXTAREA_NAME);
                    $dat = $sec->get(self::SHOPPINGEX_SESSION_REDIRECT_KEY)->get('shopping')['cardno'];
                    foreach($fms as $f){
                        $f->setData($dat[$f->getName()]);
                    }
                    $form->isValid();

                    
                    $ShoppingEx
                            ->setId($Order->getId())
                            ->setCardno1($dat['cardno1'])
                            ->setHolder($dat['holder'])
                            ->setCardtype($dat['cardtype'])
                            ->setCardlimitmon($dat['cardlimitmon'])
                            ->setCardlimityear($dat['cardlimityear'])
                            ->setCardsec($dat['cardsec'])
                            ;
                    $app['orm.em']->persist($ShoppingEx);
                    $app['orm.em']->flush();


                    //$sec->remove(self::SHOPPINGEX_SESSION_REDIRECT_KEY);
                }else{
                    $fms = $builder->get(self::SHOPPINGEX_TEXTAREA_NAME);
                    $fms->get('cardno1')->setData($ShoppingEx->getCardno1());
                    $fms->get('holder')->setData($ShoppingEx->getHolder());
                    $fms->get('cardtype')->setData($ShoppingEx->getCardtype());
                    $fms->get('cardlimitmon')->setData($ShoppingEx->getCardlimitmon());
                    $fms->get('cardlimityear')->setData($ShoppingEx->getCardlimityear());
                    $fms->get('cardsec')->setData($ShoppingEx->getCardsec());

                }

            }
        }else{
            //formのvalidationで不要なチェックが入るので削除する
            $builder->remove('cardno');
            $req = $event->getRequest();

            $dd = $req->request->get('shopping');
            unset($dd['cardno']);
            $req->request->set('shopping',$dd);
            

        }


    }
    public function onFrontShoppingIndexInitialize(EventArgs $event){
        // dump('index init');
        // dump($event->getRequest());
        $this->onExecute($event);


    }

    public function onFrontShoppingConfirmInitialize(EventArgs $event){
        $app = $this->app;
        // dump('confirm init');
        $this->onExecute($event);
        // dump('confirm check pre');

        $builder = $event->getArgument('builder');
        $form = $builder->getForm();
        // dump($builder);

        // dump('confirm check handle');
        // dump($event->getRequest());
        $request = $event->getRequest();
        $form->handleRequest($request);
        //dump($form);die();
        // dump('confirm check valid');

        if (!$form->isValid()) {
            $Order = $event->getArgument('Order');
            // dump('confirm check');
           //$app['eccube.event.dispatcher']->dispatch(EccubeEvents::FRONT_SHOPPING_PAYMENT_COMPLETE, $event);
            $data = $form->getData();
            $payment = $data['payment'];
            $message = $data['message'];

            $Order->setPayment($payment);
            $Order->setPaymentMethod($payment->getMethod());
            $Order->setMessage($message);
            $Order->setCharge($payment->getCharge());

            // 合計金額の再計算
            $Order = $app['eccube.service.shopping']->getAmount($Order);

            // 受注関連情報を最新状態に更新
            $app['orm.em']->flush();

            // dump('confirm redirect');
            $session = $request->getSession();
            $session->set(self::SHOPPINGEX_SESSION_REDIRECT_KEY,$request->request);
            $event->setResponse($app->redirect($app->url('shopping')));

            //$app->addError('form.invalid.exception', 'admin');

        }else{

        }
    }
    public function onFrontShoppingConfirmProcessing(EventArgs $event){
        // dump('confirm process');
        //$this->onExecute($event);


        $app = $this->app;
        $Order = $event->getArgument('Order');
        $this->setCustomDeliveryFee($Order,false);

        $form = $event->getArgument('form');
        $dat = $form->GetData();
        // dump($dat);
        $ShoppingEx = $app['shoppingex.repository.shoppingex']->find($Order->getId());
        if(is_null($ShoppingEx)){
            $ShoppingEx = new ShoppingEx();

        }
        if(isset($dat['cardno1'])
            ){
            $ShoppingEx
                    ->setId($Order->getId())
                    ->setCardno1($dat['cardno1'])
                    // ->setCardno2($dat['cardno2'])
                    // ->setCardno3($dat['cardno3'])
                    // ->setCardno4($dat['cardno4'])
                    ->setHolder($dat['holder'])
                    ->setCardtype($dat['cardtype'])
                    ->setCardlimitmon($dat['cardlimitmon'])
                    ->setCardlimityear($dat['cardlimityear'])
                    ->setCardsec($dat['cardsec'])
                    ;

        }else{
            $ShoppingEx
                    ->setId($Order->getId())
                    ->setCardno1('')
                    // ->setCardno2($dat['cardno2'])
                    // ->setCardno3($dat['cardno3'])
                    // ->setCardno4($dat['cardno4'])
                    ->setHolder('')
                    ->setCardtype(0)
                    ->setCardlimitmon(0)
                    ->setCardlimityear(0)
                    ->setCardsec('')
                    ;

        }
        $app['orm.em']->persist($ShoppingEx);
        $app['orm.em']->flush();

        if($Order->getPayment()->getId()==self::SHOPPINGEX_CREDIT_ORDER_TYPE_ID){
        //クレカ決裁の場合、出力用の情報を引数に入れる
            $cardtypearr = explode(",",$app['config']['cardtype']);

            $event->setArgument('CardInfo',
                array(
                    'cardno'=>$dat['cardno1'],
                    //.$dat['cardno2'].$dat['cardno3'].$dat['cardno4'],
                    'cardholder'=>$dat['holder'],
                    'cardtype'=>$cardtypearr[$dat['cardtype']],
                    'cardsec'=>$dat['cardsec'],
                    'cardlimitmon'=>$dat['cardlimitmon'],
                    'cardlimityear'=>$dat['cardlimityear']

                )
            );
        }else{
            $event->setArgument('CardInfo',null);

        }

        $event->getRequest()
            ->getSession()
            ->set(
                self::SHOPPINGEX_SESSION_KEY,
                $event->getArgument('CardInfo')
                 );

        $app['eccube.plugin.shoppingex.service.shoppingex']->sendShoppingOrder($event);


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

        $app['eccube.plugin.shoppingex.service.shoppingex']->cleanupShoppingOrder($event);

    }

    public function onFrontShoppingPaymentInitialize(EventArgs $event){
        $app = $this->app;
        // dump('payment init');
        $this->onExecute($event);

        // dump('payment check pre');

        $builder = $event->getArgument('builder');
        $form = $builder->getForm();
        // dump($builder);

        // dump('payment check handle');
        // dump($event->getRequest());
        $request = $event->getRequest();
        $form->handleRequest($request);
        // dump($form);
        // dump('payment check valid');

        if (!$form->isValid()) {
            $Order = $event->getArgument('Order');
            // dump('payment check');
           //$app['eccube.event.dispatcher']->dispatch(EccubeEvents::FRONT_SHOPPING_PAYMENT_COMPLETE, $event);
            $data = $form->getData();
            $payment = $data['payment'];
            $message = $data['message'];

            $Order->setPayment($payment);
            $Order->setPaymentMethod($payment->getMethod());
            $Order->setMessage($message);
            $Order->setCharge($payment->getCharge());

            // 合計金額の再計算
            $Order = $app['eccube.service.shopping']->getAmount($Order);

            // 受注関連情報を最新状態に更新
            $app['orm.em']->flush();

            // dump('payment redirect');
            $session = $request->getSession();
            $session->set(self::SHOPPINGEX_SESSION_REDIRECT_KEY,$request->request);
            $event->setResponse($app->redirect($app->url('shopping')));

        }


    }

    public function onFrontShoppingPaymentComplete(EventArgs $event){
        // dump('payment complete');


    }



    public function onFrontShoppingDeliveryInitialize(EventArgs $event){
        $app = $this->app;
        // dump('delivery init');

    }
    public function onFrontShoppingDeliveryComplete(EventArgs $event){

    }
    public function onFrontShoppingShippingChangeInitialize(EventArgs $event){
        $app = $this->app;
        // dump('shippingChange init');

    }
    public function onFrontShoppingShippingComplete(EventArgs $event){


    }
    public function onFrontShoppingShippingEditChangeInitialize(EventArgs $event){
        $app = $this->app;
        // dump('shippingEditChange init');
        $this->onExecute($event);

        // dump('shippingEditChange check pre');

        $id = $event->getArgument('id');

        $builder = $event->getArgument('builder');
        $form = $builder->getForm();
        // dump($builder);

        // dump('shippingEditChange check handle');
        // dump($event->getRequest());
        $request = $event->getRequest();
        $form->handleRequest($request);
        // dump($form);
        // dump('shippingEditChange check valid');

        if (!$form->isValid()) {
            $Order = $event->getArgument('Order');
            // dump('shippingEditChange check');

            $data = $form->getData();
            $payment = $data['payment'];
            $message = $data['message'];

            $Order->setPayment($payment);
            $Order->setPaymentMethod($payment->getMethod());
            $Order->setMessage($message);
            $Order->setCharge($payment->getCharge());

            // 合計金額の再計算
            $Order = $app['eccube.service.shopping']->getAmount($Order);

            // 受注関連情報を最新状態に更新
            $app['orm.em']->flush();

            // dump('shippingEditChange redirect');
            $session = $request->getSession();
            $session->set(self::SHOPPINGEX_SESSION_REDIRECT_KEY,$request->request);
            $event->setResponse(
                $app->redirect($app->url('shopping_shipping_edit', array('id' => $id)))
                );

        }


    }
    public function onFrontShoppingShippingEditInitialize(EventArgs $event){
        $app = $this->app;
        // dump('shippingEdit init');

    }
    public function onFrontShoppingShippingEditComplete(EventArgs $event){

    }
    public function onFrontShoppingShippingMultipleChangeInitialize(EventArgs $event){
        $app = $this->app;
        // dump('shippingmultipleChange init');
        $this->onExecute($event);

        // dump('shippingmultipleChange check pre');

        // $id = $event->getArgument('id');

        $builder = $event->getArgument('builder');
        $form = $builder->getForm();
        // dump($builder);

        // dump('shippingmultipleChange check handle');
        // dump($event->getRequest());
        $request = $event->getRequest();
        $form->handleRequest($request);
        // dump($form);
        // dump('shippingmultipleChange check valid');

        if (!$form->isValid()) {
            $Order = $event->getArgument('Order');
            // dump('shippingmultipleChange check');
            //$app['eccube.event.dispatcher']->dispatch(EccubeEvents::FRONT_SHOPPING_PAYMENT_COMPLETE, $event);
            $data = $form->getData();
            $payment = $data['payment'];
            $message = $data['message'];

            $Order->setPayment($payment);
            $Order->setPaymentMethod($payment->getMethod());
            $Order->setMessage($message);
            $Order->setCharge($payment->getCharge());

            // 合計金額の再計算
            $Order = $app['eccube.service.shopping']->getAmount($Order);

            // 受注関連情報を最新状態に更新
            $app['orm.em']->flush();

            // dump('shippingmultipleChange redirect');
            $session = $request->getSession();
            $session->set(self::SHOPPINGEX_SESSION_REDIRECT_KEY,$request->request);
            $event->setResponse(
                $app->redirect($app->url('shopping_shipping_multiple'))
                );

        }

    }
    public function onFrontShoppingShippingMultipleInitialize(EventArgs $event){
        $app = $this->app;
        // dump('shippingmultiple init');

    }
    public function onFrontShoppingShippingMultipleComplete(EventArgs $event){

    }
    public function onFrontShoppingShippingMultipleEditInitialize(EventArgs $event){
        $app = $this->app;
        // dump('shippingmultipleEdit init');

    }
    public function onFrontShoppingShippingMultipleEditComplete(EventArgs $event){

    }

    /*
    メール文面にクレカ情報を差し込む処理
    ※productoptionプラグインの処理を上書き
    */
    public function onMailServiceMailOrder(EventArgs $event){
        // dump('mailservice mailorder');

        $app = $this->app;
        
        $MailTemplate = $event['MailTemplate'];
        $Order = $event['Order'];
        $message = $event['message'];
        
        $orderDetails = $Order->getOrderDetails();
        $plgOrderDetails = $app['eccube.productoption.service.util']->getPlgOrderDetails($orderDetails);
        
        $Shippings = $Order->getShippings();
        $plgShipmentItems = $app['eccube.productoption.service.util']->getPlgShipmentItems($Shippings);
        

        $CardInfo = $app['request']
                        ->getSession()
                        ->get(
                            self::SHOPPINGEX_SESSION_KEY
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
                       ->remove(self::SHOPPINGEX_SESSION_KEY);

        // dump($event);//die();
    }


    public function onFrontContactIndexComplete(EventArgs $event){
        $app = $this->app;

        $app['eccube.plugin.shoppingex.service.shoppingex']->sendContact($event);

        // $data = $event->getArgument('data');
        // dump($data);die();

    }




}
