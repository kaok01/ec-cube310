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
namespace Plugin\DownloadProduct\Controller;

use Eccube\Application;
use Plugin\DownloadProduct\Form\Type;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception as HttpException;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * ダウンロード商品設定画面用コントローラー
 * Class AdminDownloadProductController
 * @package Plugin\DownloadProduct\Controller
 */
class AdminDownloadProductController
{
    /**
     * AdminDownloadProductController constructor.
     */
    public function __construct()
    {
    }

    /**
     * ダウンロード商品機能　基本情報管理設定画面
     * @param Application $app
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function index(Application $app, Request $request)
    {
        $app['monolog.downloadproduct.admin']->addInfo('index start');

        // 最終保存のダウンロード商品設定情報取得
        $DownloadProductInfo = $app['eccube.plugin.downloadproduct.repository.downloadproductinfo']->getLastInsertData();

        $form = $app['form.factory']
            ->createBuilder('admin_downloadproduct_info', $DownloadProductInfo)
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $DownloadProductInfo = $form->getData();
            $app['eccube.plugin.downloadproduct.repository.downloadproductinfo']->save($DownloadProductInfo);

            $app->addSuccess('admin.downloadproduct.save.complete', 'admin');

            $app['monolog.downloadproduct.admin']->addInfo(
                'index save',
                array(
                    'saveData' => $app['serializer']->serialize($DownloadProductInfo, 'json'),
                )
            );

            $app['monolog.downloadproduct.admin']->addInfo('index end');

            return $app->redirect($app->url('downloadproduct_info'));
        }

        $app['monolog.downloadproduct.admin']->addInfo('index end');

        return $app->render(
            'DownloadProduct/Resource/template/admin/downloadproductinfo.twig',
            array(
                'form' => $form->createView(),
                'DownloadProduct' => $DownloadProductInfo,
            )
        );
    }
}
