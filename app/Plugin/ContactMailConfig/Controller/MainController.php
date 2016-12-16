<?php

/*
 * Copyright(c) 2015 GMO Payment Gateway, Inc. All rights reserved.
 * http://www.gmo-pg.com/
 */

namespace Plugin\ContactMailConfig\Controller;

use Doctrine\ORM\EntityManager;
use Eccube\Application;
use Eccube\Entity\Master\CsvType;
use Eccube\Entity\Product;
use Eccube\Exception\CsvImportException;
use Eccube\Service\CsvImportService;
use Eccube\Util\Str;
use Plugin\GmoPaymentGateway\Controller\Util\PaymentUtil;
use Plugin\GmoPaymentGateway\Controller\Util\PluginUtil;
use Plugin\GmoPaymentGateway\Form\Type\ConfigType;
use Plugin\ContactMailConfig\Entity\ContactMailConfig;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Yaml\Yaml;

/**
 * Controller to handle module setting screen
 */
class MainController {

    private $indexTwig = 'ContactMailConfig/Resource/template/admin/index.twig';

    /**
     * メイン
     */
    public function index(Application $app, Request $request)
    {
        $BaseInfo = $app['eccube.repository.base_info']->get();


        $MailConfig = $app['eccube.repository.plugin.ContactMailConfig']->findOneBy(array('type' => 'contact'));

        if(!$MailConfig){
            $MailConfig = new ContactMailConfig();
            $MailConfig->setType('contact');
            $subject = '[' . $BaseInfo->getShopName() . '] お問い合わせを受け付けました。';
            $MailConfig->setSubject($subject);
            $app['orm.em']->persist($MailConfig);
        }

        /* @var $form Form */
        $form = $app['form.factory']->createBuilder('admin_mail_config', $MailConfig)->getForm();
//        $form->setData($MailConfig);

        if ('POST' === $request->getMethod()) {
            $form->handleRequest($request);
            $MailConfig = $form->getData();
            if ($form->isValid()) {
                $app['orm.em']->flush($MailConfig);
                $app->addSuccess('設定を保存しました', 'admin');
            }
        }

        return $app->render($this->indexTwig, array(
            'form' => $form->createView(),
        ));
    }

}
