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
namespace Plugin\DownloadProduct;

use Eccube\Application;
use Eccube\Event\EventArgs;
use Eccube\Event\TemplateEvent;
use Eccube\Exception\ShoppingException;
use Eccube\Exception\CartException;
use Eccube\Entity\CustomerAddress;

use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Eccube\Common\Constant;

/**
 * ダウンロード商品プラグインイベント処理ルーティングクラス
 * Class DownloadProductEvent
 * @package Plugin\DownloadProduct
 */
class DownloadProductEvent
{
    /**
     * @var string 非会員用セッションキー
     */
    private $sessionKey = 'eccube.front.shopping.nonmember';

    /**
     * @var string 非会員用セッションキー
     */
    private $sessionCustomerAddressKey = 'eccube.front.shopping.nonmember.customeraddress';



    /** @var  \Eccube\Application $app */
    protected $app;

    /**
     * DownloadProductEvent constructor.
     * @param $app
     */
    public function __construct($app)
    {
        $this->app = $app;
    }

    /**
     * フロント画面権限確認
     *
     * @return bool
     */
    protected function isAuthRouteFront()
    {
        return $this->app->isGranted('ROLE_USER');
    }

    public function onRenderAdminOrderIndex(TemplateEvent $event){
        return;
        $helper = new AdminOrderIndex();
        $helper->createTwig($event);
    }
    public function onRenderAdminOrderEdit(TemplateEvent $event){
        return;
        $helper = new AdminOrderEdit();
        $helper->createTwig($event);
    }

    public function onFrontShoppingIndexInitialize(EventArgs $event){


    }

    public function onFrontShoppingConfirmInitialize(EventArgs $event){
        $app=$this->app;
        $req=$event->getRequest();
        $sec = $req->getSession();
        // $form = $event->getArgument('form');
        // $email = $form['email']->getData();

        $nonmember = $sec->get($this->sessionKey);
        if($nonmember['customer']){

            $email = $nonmember['customer']->getEmail();


            $service=$app['eccube.plugin.downloadproduct.service.download'];
            $sec->set('eccube.plugin.downloadproduct.nonmember',
                array(
                    'emailcheck' => false
                )
            );

            if($service->ConfirmEmailCustomerExist($email)){
                $sec->set('eccube.plugin.downloadproduct.nonmember',
                    array(
                        'emailcheck' => false
                    )
                );

                $event->setResponse($app->redirect($app->url('shopping_nonmember')));
            }

            return;


        }

    }
    public function onFrontShoppingConfirmProcessing(EventArgs $event){



    }

    public function onFrontShoppingConfirmComplete(EventArgs $event){


    }

    public function onFrontShoppingPaymentInitialize(EventArgs $event){




    }

    public function onFrontShoppingPaymentComplete(EventArgs $event){



    }



    public function onFrontShoppingDeliveryInitialize(EventArgs $event){



    }
    public function onFrontShoppingDeliveryComplete(EventArgs $event){

    }
    public function onFrontShoppingShippingChangeInitialize(EventArgs $event){



    }
    public function onFrontShoppingShippingComplete(EventArgs $event){


    }
    public function onFrontShoppingShippingEditChangeInitialize(EventArgs $event){

    }
    public function onFrontShoppingShippingEditInitialize(EventArgs $event){



    }
    public function onFrontShoppingShippingEditComplete(EventArgs $event){

    }



    public function onFrontShoppingNonmemberInitialize(EventArgs $event){
        $req = $event->getRequest();
        $sec = $req->getSession();
        $formdata = $sec->get('test_nonmember');
        $response = $event->getResponse();
        $formdata222 = $sec->get($this->sessionKey);
dump($formdata222);
dump('response');
dump($response);
        if($formdata){
dump('response');
            //$req->set();
            $builder = $event->getArgument('builder');
            $form = $event->getArgument('form');

            //$form = $builder->getForm();
dump($formdata);
dump($req); 
            $tokenkey = Constant::TOKEN_NAME;
dump($tokenkey); 
                $prefid = $formdata[0]['pref']['id'];
                $pref = $this->app['eccube.repository.master.pref']->find($prefid);
dump($pref); 
                $formdata[0]['pref']= $pref;
                $reqdata = array(
                        //$tokenkey => null,
                        'name'=>
                            array(
                                'name01'=>$formdata[0]['name01'],
                                'name02'=>$formdata[0]['name02'],
                            ),
                        'kana'=>
                            array(
                                'kana01'=>$formdata[0]['kana01'],
                                'kana02'=>$formdata[0]['kana02'],
                            ),

                        'company_name'=>$formdata[0]['company_name'],
                        'zip'=>
                            array(
                                'zip01'=>$formdata[0]['zip01'],
                                'zip02'=>$formdata[0]['zip02'],
                            ),

                        'address'=>
                            array(
                                'pref'=>$formdata[0]['pref']['id'],
                                'addr01'=>$formdata[0]['addr01'],
                                'addr02'=>$formdata[0]['addr02'],
                            ),

                        'tel'=>
                            array(
                                'tel01'=>$formdata[0]['tel01'],
                                'tel02'=>$formdata[0]['tel02'],
                                'tel03'=>$formdata[0]['tel03'],
                            ),

                        'email'=>
                            array(
                                'first'=>$formdata[0]['email'],
                                'second'=>$formdata[0]['email'],
                            ),

                    );
dump($reqdata);
            // $form['name']=array(
            //                     'name01'=>$formdata[0]['name01'],
            //                     'name02'=>$formdata[0]['name02'],

            // );//->bind($reqdata);
            $form->setData($formdata[0]);
            //$form->bind();
            //$form->submit($reqdata);
            //$req->request->set('nonmember',$reqdata);
            //$form->handleRequest($req);
dump($form);
dump($req);

        }

    }
    public function onFrontShoppingNonmemberComplete(EventArgs $event){

        $app=$this->app;
        $order = $event->getArgument('Order');
dump('order');
dump($order);
        $form = $event->getArgument('form');
        $email = $form['email']->getData();
        $service=$app['eccube.plugin.downloadproduct.service.download'];

        if($service->ConfirmEmailCustomerExist($email)){

            $event->setResponse(
             $this->app->render('Shopping/nonmember.twig', array(
                'form' => $form->createView(),
                'error' => 'このメールアhhドレスは既に登録されています。変更、または、前に戻りログインしてお進みください。'
            )
             )
            );
            return;         

        }
dump('comp');
dump($form);

        $req = $event->getRequest();
        $sec = $req->getSession();
        $customer = $form->getData();
        dump($sec);
        $sec->set('test_nonmember',array($form->getData()));
dump($sec->get('test_nonmember'));

        if(is_null($order)){
            return;
        }
dump('order');
dump($order);
        $order
                ->setName01($customer['name01'])
                ->setName02($customer['name02'])
                ->setKana01($customer['kana01'])
                ->setKana02($customer['kana02'])
                ->setCompanyName($customer['company_name'])
                ->setEmail($customer['email'])
                ->setTel01($customer['tel01'])
                ->setTel02($customer['tel02'])
                ->setTel03($customer['tel03'])
                ->setZip01($customer['zip01'])
                ->setZip02($customer['zip02'])
                ->setZipCode($customer['zip01'].$customer['zip02'])
                ->setPref($customer['pref'])
                ->setAddr01($customer['addr01'])
                ->setAddr02($customer['addr02']);

dump($order);
        // 非会員用セッションを作成

dump('nonmember');
        $nonMember = $this->app['session']->get($this->sessionKey);

        if($nonMember){
            $nonMember['customer']
                ->setName01($customer['name01'])
                ->setName02($customer['name02'])
                ->setKana01($customer['kana01'])
                ->setKana02($customer['kana02'])
                ->setCompanyName($customer['company_name'])
                ->setEmail($customer['email'])
                ->setTel01($customer['tel01'])
                ->setTel02($customer['tel02'])
                ->setTel03($customer['tel03'])
                ->setZip01($customer['zip01'])
                ->setZip02($customer['zip02'])
                ->setZipCode($customer['zip01'].$customer['zip02'])
                ->setPref($customer['pref'])
                ->setAddr01($customer['addr01'])
                ->setAddr02($customer['addr02']);
            $nonMember['pref'] = $customer['pref']->getId();


            $this->app['session']->set($this->sessionKey, $nonMember);
        }
dump($nonMember);

dump('customerAddresses');

        $customerAddresses = $this->app['session']->get($this->sessionCustomerAddressKey);
dump($customerAddresses);
        if($customerAddresses){
dump('1');
            $customerAddresses = unserialize($customerAddresses);
            $CustomerAddress = new CustomerAddress();
dump('2');
            $CustomerAddress
                ->setCustomer($nonMember['customer'])
                ->setName01($customer['name01'])
                ->setName02($customer['name02'])
                ->setKana01($customer['kana01'])
                ->setKana02($customer['kana02'])
                ->setCompanyName($customer['company_name'])
                ->setTel01($customer['tel01'])
                ->setTel02($customer['tel02'])
                ->setTel03($customer['tel03'])
                ->setZip01($customer['zip01'])
                ->setZip02($customer['zip02'])
                ->setZipCode($customer['zip01'].$customer['zip02'])
                ->setPref($customer['pref'])
                ->setAddr01($customer['addr01'])
                ->setAddr02($customer['addr02'])
                ->setDelFlg(Constant::DISABLED);
            $nonMember['customer']->addCustomerAddress($CustomerAddress);
            $customerAddresses = array();
            $customerAddresses[] = $CustomerAddress;
            $this->app['session']->set($this->sessionCustomerAddressKey, serialize($customerAddresses));

            // 受注情報を作成
            try {
                // 受注情報を作成
                $app['eccube.service.shopping']->createOrder($nonMember['customer']);
            } catch (CartException $e) {
                $app->addRequestError($e->getMessage());
                return $app->redirect($app->url('cart'));
            }
dump('5');

        }
dump($customerAddresses);
dump('99');


         // die();
        //$email->AddError('このげげげ');
            //$form->get('email')->addError('このメールアドレスは既に登録されています。ログインしてお進みください。');


    }
    public function onRenderShoppingNonMember(TemplateEvent $event){

        $sec = $this->app['session'];
        $nonmember = $sec->get('eccube.plugin.downloadproduct.nonmember');
        if($nonmember){
            if($nonmember['emailcheck']===true){
                //
            }else{
                $param = $event->getParameters();
                $param['error'] = 'ccccccccこのメールアドレスは既に登録されています。変更、または、前に戻りログインしてお進みください。';

                $event->setParameters($param);
            }
        }

    }



    public function onFrontShoppingShippingMultipleChangeInitialize(EventArgs $event){



    }
    public function onFrontShoppingShippingMultipleInitialize(EventArgs $event){


    }
    public function onFrontShoppingShippingMultipleComplete(EventArgs $event){

    }
    public function onFrontShoppingShippingMultipleEditInitialize(EventArgs $event){


    }
    public function onFrontShoppingShippingMultipleEditComplete(EventArgs $event){

    }

    /*
    メール文面に情報を差し込む処理
    プラグインの処理を上書き
    */
    public function onMailServiceMailOrder(EventArgs $event){

    }


    public function onFrontContactIndexComplete(EventArgs $event){
        $app = $this->app;

        // $app['eccube.plugin.shoppingex.service.shoppingex']->sendContact($event);


    }
    public function onFrontProductDetailInitialize(EventArgs $event){
        $app = $this->app;
dump('p detail idx init');

        // $app['eccube.plugin.shoppingex.service.shoppingex']->sendContact($event);


    }
    public function onFrontProductDetailComplete(EventArgs $event){
        $app = $this->app;
dump('p detail comp');
        // $req=$event->getRequest();
        // $req['mode']=

        // $app['eccube.plugin.shoppingex.service.shoppingex']->sendContact($event);

    }
    public function onRenderProductDetail(TemplateEvent $event){

    }



    public function onFrontCartIndexInitialize(EventArgs $event){
        $app = $this->app;
dump('cart idx init');

        // $app['eccube.plugin.shoppingex.service.shoppingex']->sendContact($event);


    }
    public function onFrontCartIndexComplete(EventArgs $event){
        $app = $this->app;

        // $app['eccube.plugin.shoppingex.service.shoppingex']->sendContact($event);


    }

    public function onFrontCartAddInitialize(EventArgs $event){
        $app = $this->app;
dump('cart add init');
        // $app['eccube.plugin.shoppingex.service.shoppingex']->sendContact($event);
        $pcid = $event->getArgument('productClassId');
        $num = $event->getArgument('quantity');
        $event->setArgument('quantity',0);

    }
    public function onFrontCartAddComplete(EventArgs $event){
        $app = $this->app;
dump('cart add comp');
die();
        // $app['eccube.plugin.shoppingex.service.shoppingex']->sendContact($event);


    }
    public function onFrontCartAddException(EventArgs $event){
        $app = $this->app;

        // $app['eccube.plugin.shoppingex.service.shoppingex']->sendContact($event);


    }

}
