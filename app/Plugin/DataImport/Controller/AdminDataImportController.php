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
namespace Plugin\DataImport\Controller;

use Eccube\Application;
use Plugin\DataImport\Form\Type;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception as HttpException;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * ポイント設定画面用コントローラー
 * Class AdminDataImportController
 * @package Plugin\DataImport\Controller
 */
class AdminDataImportController
{
    /**
     * AdminDataImportController constructor.
     */
    public function __construct()
    {
    }

    /**
     * ポイント基本情報管理設定画面
     * @param Application $app
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function index(Application $app, Request $request)
    {
        $app['monolog.dataimport.admin']->addInfo('index start');

        // 最終保存のポイント設定情報取得
        $DataImportInfo = $app['eccube.plugin.dataimport.repository.dataimportinfo']->getLastInsertData();

        $form = $app['form.factory']
            ->createBuilder('admin_dataimport_info', $DataImportInfo)
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $DataImportInfo = $form->getData();
            $app['eccube.plugin.dataimport.repository.dataimportinfo']->save($DataImportInfo);

            $app->addSuccess('admin.dataimport.save.complete', 'admin');

            $app['monolog.dataimport.admin']->addInfo(
                'index save',
                array(
                    'saveData' => $app['serializer']->serialize($DataImportInfo, 'json'),
                )
            );

            $app['monolog.dataimport.admin']->addInfo('index end');

            return $app->redirect($app->url('dataimport_info'));
        }

        $app['monolog.dataimport.admin']->addInfo('index end');

        return $app->render(
            'DataImport/Resource/template/admin/dataimportinfo.twig',
            array(
                'form' => $form->createView(),
                'DataImport' => $DataImportInfo,
            )
        );
    }
}
