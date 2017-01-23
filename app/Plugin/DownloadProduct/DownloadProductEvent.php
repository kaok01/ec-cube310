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

use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * ダウンロード商品プラグインイベント処理ルーティングクラス
 * Class DownloadProductEvent
 * @package Plugin\DownloadProduct
 */
class DownloadProductEvent
{

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
        $helper = new AdminOrderIndex();
        $helper->createTwig($event);
    }
    public function onRenderAdminOrderEdit(TemplateEvent $event){
        $helper = new AdminOrderEdit();
        $helper->createTwig($event);
    }

    public function onFrontShoppingIndexInitialize(EventArgs $event){


    }

    public function onFrontShoppingConfirmInitialize(EventArgs $event){
        $app=$this->app;

        // $form = $event->getArgument('form');
        // $email = $form['email']->getData();
        // $service=$app['eccube.plugin.downloadproduct.service.download'];

        //if($service->ConfirmEmailCustomerExist($email)){
        $event->setResponse($app->redirect($app->url('shopping_nonmember')));
        return;

            $app->redirect();
            $event->setResponse(
             $this->app->render('Shopping/nonmember.twig', array(
                'form' => $form->createView(),
                'error' => 'このメールアドレスは既に登録されています。変更、または、前に戻りログインしてお進みください。'
            )
             )
            );            

        //}


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
dump('response');
dump($response);
        if($formdata or $response instanceof RedirectResponse){
            //$req->set();
            $builder = $event->getArgument('builder');
            $form = $builder->getForm();
            //$form->setData($formdata[0]);
dump($formdata);
dump($req); 
                $reqdata = array(
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
            $req->request->set('nonmember',$reqdata);
dump($req);
//die();
        }

    }
    public function onFrontShoppingNonmemberComplete(EventArgs $event){

        $app=$this->app;

        $form = $event->getArgument('form');
        $email = $form['email']->getData();
        $service=$app['eccube.plugin.downloadproduct.service.download'];

        if($service->ConfirmEmailCustomerExist($email)){

            $event->setResponse(
             $this->app->render('Shopping/nonmember.twig', array(
                'form' => $form->createView(),
                'error' => 'このメールアドレスは既に登録されています。変更、または、前に戻りログインしてお進みください。'
            )
             )
            );
            return;         

        }
        $req = $event->getRequest();
        $sec = $req->getSession();
dump($sec);
        $sec->set('test_nonmember',array($form->getData()));
dump($sec->get('test_nonmember'));
        //die();
        //$email->AddError('このげげげ');
            //$form->get('email')->addError('このメールアドレスは既に登録されています。ログインしてお進みください。');


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


}
