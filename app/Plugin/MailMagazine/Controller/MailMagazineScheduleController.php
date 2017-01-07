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

namespace Plugin\MailMagazine\Controller;

use Eccube\Application;
use Eccube\Common\Constant;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception as HttpException;
use Symfony\Component\Form\FormError;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class MailMagazineScheduleController
{
    private $main_title;
    private $sub_title;

    public function __construct()
    {
    }

    /**
     * 配信内容設定検索画面を表示する.
     * 左ナビゲーションの選択はGETで遷移する.
     *
     * @param Application $app
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function index(Application $app, Request $request)
    {
        $pagination = null;

        $SendScheduleRepo = $app['eccube.plugin.mail_magazine.repository.mail_magazine_schedule'];
        $SendSchedule = $SendScheduleRepo->findAll();
dump($SendSchedule);
        return $app->render('MailMagazine/View/admin/schedule_list.twig', array(
            'pagination' => $SendSchedule,
        ));
    }


    /**
     * 配信処理
     * 配信終了後配信履歴に遷移する
     * RequestがPOST以外の場合はBadRequestHttpExceptionを発生させる
     * @param Application $app
     * @param Request $request
     * @param string $id
     */
    public function edit(Application $app, Request $request, $id = null) {

        // POSTでない場合は終了する
        if ('POST' !== $request->getMethod()) {
            throw new BadRequestHttpException();
        }

        // Formを取得する
        $form = $app['form.factory']
            ->createBuilder('mail_magazine', null)
            ->getForm();
        $form->handleRequest($request);
        $data = $form->getData();


        $scheduleform = $app['form.factory']
                    ->createBuilder('mail_magazine_schedule', null)
                    ->getForm();
        $scheduleform->handleRequest($request);
        $scheduledata = $scheduleform->getData();



        // 送信対象者をdtb_customerから取得する
        if (!$form->isValid()) {
            throw new BadRequestHttpException();
        }

        // 送信対象者をdtb_customerから取得する
        if (!$scheduleform->isValid()) {
            throw new BadRequestHttpException();
        }
dump($data);
dump($scheduledata);
die();
        // サービスの取得
        $service = $app['eccube.plugin.mail_magazine.service.mail'];

        // 配信履歴を登録する
        $sendId = $service->createMailMagazineHistory($data);
        if(is_null($sendId)) {
            $app->addError('admin.mailmagazine.send.regist.failure', 'admin');
        } else {

            // 登録した配信履歴に関連付けてスケジュール配信を記録する
            $service->createReservedsendMailMagazine($sendId,$scheduledata);

            $app->addSuccess('admin.mailmagazine.reservedsend.regist', 'admin');
        }


        // 配信管理画面に遷移する
        return $app->redirect($app->url('admin_mail_magazine_history'));
    }


    /**
     * 配信処理
     * 配信終了後配信履歴に遷移する
     * RequestがPOST以外の場合はBadRequestHttpExceptionを発生させる
     * @param Application $app
     * @param Request $request
     * @param string $id
     */
    public function delete(Application $app, Request $request, $id = null) {

        // POSTでない場合は終了する
        if ('POST' !== $request->getMethod()) {
            throw new BadRequestHttpException();
        }

        // Formを取得する
        $form = $app['form.factory']
            ->createBuilder('mail_magazine', null)
            ->getForm();
        $form->handleRequest($request);
        $data = $form->getData();


        $scheduleform = $app['form.factory']
                    ->createBuilder('mail_magazine_schedule', null)
                    ->getForm();
        $scheduleform->handleRequest($request);
        $scheduledata = $scheduleform->getData();



        // 送信対象者をdtb_customerから取得する
        if (!$form->isValid()) {
            throw new BadRequestHttpException();
        }

        // 送信対象者をdtb_customerから取得する
        if (!$scheduleform->isValid()) {
            throw new BadRequestHttpException();
        }
dump($data);
dump($scheduledata);
die();
        // サービスの取得
        $service = $app['eccube.plugin.mail_magazine.service.mail'];

        // 配信履歴を登録する
        $sendId = $service->createMailMagazineHistory($data);
        if(is_null($sendId)) {
            $app->addError('admin.mailmagazine.send.regist.failure', 'admin');
        } else {

            // 登録した配信履歴に関連付けてスケジュール配信を記録する
            $service->createReservedsendMailMagazine($sendId,$scheduledata);

            $app->addSuccess('admin.mailmagazine.reservedsend.regist', 'admin');
        }


        // 配信管理画面に遷移する
        return $app->redirect($app->url('admin_mail_magazine_history'));
    }

    /**
    *
    * @param Application $app
    * @param Request $request
    * @param unknown $id
    * @throws NotFoundHttpException
    * @return \Symfony\Component\HttpFoundation\RedirectResponse
    */
    public function up(Application $app, Request $request, $id)
    {
        $repos = $app['eccube.plugin.mail_magazine.repository.maker'];

        $TargetMailMagazine = $repos->find($id);
        if (!$TargetMailMagazine) {
            throw new NotFoundHttpException();
        }

        $form = $app['form.factory']
            ->createNamedBuilder('admin_mail_magazine', 'form', null, array(
                'allow_extra_fields' => true,
            ))
            ->getForm();

        $status = false;
        if ($request->getMethod() === 'POST') {
            $form->handleRequest($request);
            if ($form->isValid()) {
                $status = $repos->up($TargetMailMagazine);
            }
        }

        if ($status === true) {
            $app->addSuccess('admin.maker.down.complete', 'admin');
        } else {
            $app->addError('admin.maker.down.error', 'admin');
        }

        return $app->redirect($app->url('admin_mail_magazine'));
    }

    /**
    *
    * @param Application $app
    * @param Request $request
    * @param unknown $id
    * @throws NotFoundHttpException
    */
    public function down(Application $app, Request $request, $id)
    {
        $repos = $app['eccube.plugin.mail_magazine.repository.maker'];

        $TargetMailMagazine = $repos->find($id);
        if (!$TargetMailMagazine) {
            throw new NotFoundHttpException();
        }

        $form = $app['form.factory']
            ->createNamedBuilder('admin_mail_magazine', 'form', null, array(
                'allow_extra_fields' => true,
            ))
            ->getForm();

        $status = false;
        if ($request->getMethod() === 'POST') {
            $form->handleRequest($request);
            if ($form->isValid()) {
                $status = $repos->down($TargetMailMagazine);
            }
        }

        if ($status === true) {
            $app->addSuccess('admin.mail.down.complete', 'admin');
        } else {
            $app->addError('admin.mail.down.error', 'admin');
        }

        return $app->redirect($app->url('admin_mail_magazine'));
    }

}
