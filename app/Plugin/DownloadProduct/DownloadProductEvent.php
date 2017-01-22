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

    /**
     * @var string 受注IDキー
     */
    private $sessionOrderKey = 'eccube.front.shopping.order.id';


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
        $Order = $event->getArgument('Order');
        // $email = $form['email']->getData();

        $nonmember = $sec->get($this->sessionKey);
        if($nonmember['customer']){

            $email = $nonmember['customer']->getEmail();


            $service=$app['eccube.plugin.downloadproduct.service.download'];
            $sec->set('eccube.plugin.downloadproduct.nonmember',
                array(
                    'emailcheck' => true
                )
            );

            if($service->ConfirmEmailCustomerExist($email)){
                $sec->set('eccube.plugin.downloadproduct.nonmember',
                    array(
                        'emailcheck' => false
                    )
                );

                $event->setResponse($app->redirect($app->url('shopping_nonmember')));
                return;
            }

        }

    }
    public function onFrontShoppingConfirmProcessing(EventArgs $event){



    }

    public function onFrontShoppingConfirmComplete(EventArgs $event){

        $app=$this->app;
        $req=$event->getRequest();
        $sec = $req->getSession();
        $Order = $event->getArgument('Order');
        // $email = $form['email']->getData();

        $nonmember = $sec->get($this->sessionKey);
        if($nonmember['customer']){
            $customeraddress=$sec->get($this->sessionCustomerAddressKey);
            if($customeraddress){
                $customeraddress=unserialize($customeraddress);

            }else{
                $customeraddress=null;
            }
            //会員情報を登録
            $this->createNonMemberCustomerOrder($Order,$nonmember,$customeraddress);

        }


    }

    private function createNonMemberCustomerOrder(&$Order,$nonmember,$customeraddress){
        $app=$this->app;
        $sec=$app['session'];
        $_Customer=$nonmember['customer'];

        $Customer = $app['eccube.repository.customer']->newCustomer();
        $CustomerAddress = new \Eccube\Entity\CustomerAddress();
        $Customer->setBuyTimes(1);
        $Customer->setBuyTotal($Order->getTotal());
        $Customer->setFirstBuyDate(new \Datetime());
        $Customer->setLastBuyDate(new \Datetime());

        $CustomerStatus = $app['eccube.repository.customer_status']->find(2);
        $Pref = $app['eccube.repository.master.pref']->find($_Customer->getPref()->getId());
        $Customer->setStatus($CustomerStatus);
        $Customer
            ->setName01($_Customer->getName01())
            ->setName02($_Customer->getName02())
            ->setKana01($_Customer->getKana01())
            ->setKana02($_Customer->getKana02())
            ->setCompanyName($_Customer->getCompanyName())
            ->setZip01($_Customer->getZip01())
            ->setZip02($_Customer->getZip02())
            ->setZipcode($_Customer->getZip01() . $_Customer->getZip02())
            ->setPref($Pref)
            ->setAddr01($_Customer->getAddr01())
            ->setAddr02($_Customer->getAddr02())
            ->setTel01($_Customer->getTel01())
            ->setTel02($_Customer->getTel02())
            ->setTel03($_Customer->getTel03())
            ->setFax01($_Customer->getFax01())
            ->setFax02($_Customer->getFax02())
            ->setFax03($_Customer->getFax03())
            ->setEmail($_Customer->getEmail())
            ->setDelFlg(Constant::DISABLED);

        $Customer->setPassword($app['config']['default_password']);
        $Customer->setSalt(
            $app['eccube.repository.customer']->createSalt(5)
        );
        $Customer->setSecretKey(
            $app['eccube.repository.customer']->getUniqueSecretKey($app)
        );

        $Customer->setPassword(
            $app['eccube.repository.customer']->encryptPassword($app, $Customer)
        );

        $app['orm.em']->persist($Customer);

        $app['orm.em']->flush($Customer);
        if($customeraddress){
            $_CustomerAddress=$customeraddress[0];
            $Pref = $app['eccube.repository.master.pref']->find($_CustomerAddress->getPref()->getId());

            $CustomerAddress
                ->setName01($_CustomerAddress->getName01())
                ->setName02($_CustomerAddress->getName02())
                ->setKana01($_CustomerAddress->getKana01())
                ->setKana02($_CustomerAddress->getKana02())
                ->setCompanyName($_CustomerAddress->getCompanyName())
                ->setZip01($_CustomerAddress->getZip01())
                ->setZip02($_CustomerAddress->getZip02())
                ->setZipcode($_CustomerAddress->getZip01() . $_CustomerAddress->getZip02())
                ->setPref($Pref)
                ->setAddr01($_CustomerAddress->getAddr01())
                ->setAddr02($_CustomerAddress->getAddr02())
                ->setTel01($_CustomerAddress->getTel01())
                ->setTel02($_CustomerAddress->getTel02())
                ->setTel03($_CustomerAddress->getTel03())
                ->setFax01($_CustomerAddress->getFax01())
                ->setFax02($_CustomerAddress->getFax02())
                ->setFax03($_CustomerAddress->getFax03())
                ->setDelFlg(Constant::DISABLED)
                ->setCustomer($Customer);


            $app['orm.em']->persist($CustomerAddress);
            $app['orm.em']->flush($CustomerAddress);

        }

        $Order->setCustomer($Customer);

        $app['orm.em']->persist($Order);
        $app['orm.em']->flush($Order);

        if(isset($app['eccube.plugin.mail_magazine.service.mail'])){
            $Ms = $app['eccube.plugin.mail_magazine.service.mail'];
            $Ms->saveMailmagaCustomer($Customer->getId(),1);
        }



        $nonmember['customer']=$Customer;

        $sec->set($this->sessionKey,$nonmember);
        $sec->remove($this->sessionCustomerAddressKey);


    }
    public function onFrontShoppingCompleteInitialize(EventArgs $event){
        $app = $this->app;
        $nonmember=$app['session']->get($this->sessionKey);
        if($nonmember['customer']){
            $orderId=$event->getArgument('orderId');
            $email=$nonmember['customer']->getEmail();
            // 非会員情報を削除
            $app['session']->remove($this->sessionKey);

            // 受注IDセッションを削除
            $app['session']->remove($this->sessionOrderKey);


            try{
                $service=$app['eccube.plugin.downloadproduct.service.download'];

                $service->SendNonMemberResetMail($email);

            } catch (\Exception $e) {
                $app->addRequestError('会員情報の送信に失敗しました。');
                $event->setResponse(
                 $app->redirect($app->url('cart'))
                 );
            }

            $event->setResponse(
                    $app->render('Shopping/complete_nonmember.twig', array(
                        'orderId' => $orderId,
                    ))

                );


        }


    }    
    public function onFrontShoppingPaymentInitialize(EventArgs $event){

            // $event->setResponse(
            //         $app->render('Shopping/complete_nonmember.twig', array(
            //             'orderId' => $orderId,
            //         ))

            //     );


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

        if($formdata){

            $builder = $event->getArgument('builder');
            //本体で参照わたしでいれてる（本体のカスタマイズ）。初期値設定のため。
            $form = $event->getArgument('form');

            //$form = $builder->getForm();
            $prefid = $formdata[0]['pref']['id'];
            $pref = $this->app['eccube.repository.master.pref']->find($prefid);
            $formdata[0]['pref']= $pref;

            $form->setData($formdata[0]);

        }

    }
    public function onFrontShoppingNonmemberComplete(EventArgs $event){

        $app=$this->app;
        $order = $event->getArgument('Order');

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

        $req = $event->getRequest();
        $sec = $req->getSession();
        $customer = $form->getData();
        dump($sec);
        $sec->set('test_nonmember',array($form->getData()));
        if(is_null($order)){
            return;
        }

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

        // 非会員用セッションを作成
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

        $customerAddresses = $this->app['session']->get($this->sessionCustomerAddressKey);
        if($customerAddresses){

            $customerAddresses = unserialize($customerAddresses);
            $CustomerAddress = new CustomerAddress();

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


        }

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


        // $app['eccube.plugin.shoppingex.service.shoppingex']->sendContact($event);


    }
    public function onFrontProductDetailComplete(EventArgs $event){
        $app = $this->app;

        // $req=$event->getRequest();
        // $req['mode']=

        // $app['eccube.plugin.shoppingex.service.shoppingex']->sendContact($event);

    }
    public function onRenderProductDetail(TemplateEvent $event){

    }



    public function onFrontCartIndexInitialize(EventArgs $event){
        $app = $this->app;


        // $app['eccube.plugin.shoppingex.service.shoppingex']->sendContact($event);


    }
    public function onFrontCartIndexComplete(EventArgs $event){
        $app = $this->app;

        // $app['eccube.plugin.shoppingex.service.shoppingex']->sendContact($event);


    }

    public function onRenderCart(TemplateEvent $event){

    }


    public function onFrontCartAddInitialize(EventArgs $event){
        $app = $this->app;

        // // $app['eccube.plugin.shoppingex.service.shoppingex']->sendContact($event);
        // $pcid = $event->getArgument('productClassId');
        // $num = $event->getArgument('quantity');
        // $event->setArgument('quantity',0);

    }
    public function onFrontCartAddComplete(EventArgs $event){
        $app = $this->app;


    }
    public function onFrontCartAddException(EventArgs $event){
        $app = $this->app;

        // $app['eccube.plugin.shoppingex.service.shoppingex']->sendContact($event);


    }

}
