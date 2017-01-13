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
    public function test(Application $app, Request $request){
        dump('test');
        $app['eccube.plugin.mail_magazine.service.mail']->ScheduleExec();
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
        $searchForm = $app['form.factory']
            ->createBuilder()
            ->getForm();
        $searchForm->handleRequest($request);
        $searchData = $searchForm->getData();

        $pageNo = $request->get('page_no');

        $qb = $app['orm.em']->createQueryBuilder();
        $qb->select("d")
            ->from("\Plugin\MailMagazine\Entity\MailMagazineSendSchedule", "d")
            ->where("d.del_flg = :delFlg")
            ->setParameter('delFlg', Constant::DISABLED)
            ->orderBy("d.send_start", "DESC");

        $pagination = $app['paginator']()->paginate(
                $qb,
                empty($pageNo) ? 1 : $pageNo,
                empty($searchData['pagemax']) ? 10 : $searchData['pagemax']->getId()
        );
dump($pagination);
        foreach($pagination as $item){
            if(!is_null($item->getSendWeek())){
                $v = $item->getSendWeek();
                $item->setSendWeek(unserialize(base64_decode($v)));

            }

        }
        return $app->render('MailMagazine/View/admin/schedule_list.twig', array(
            'pagination' => $pagination
        ));
    }

    /**
     * preview画面表示
     * @param Application $app
     * @param Request $request
     * @param unknown $id
     * @return void|\Symfony\Component\HttpFoundation\Response
     */
    public function preview(Application $app, Request $request, $id)
    {

        // id の存在確認
        // nullであれば一覧に戻る
        if(is_null($id) || strlen($id) == 0) {
            $app->addError('admin.mailmagazine.template.data.illegalaccess', 'admin');

            // メルマガテンプレート一覧へリダイレクト
            return $app->redirect($app->url('admin_mail_magazine_template'));
        }

        // パラメータ$idにマッチするデータが存在するか判定
        // あれば、subject/bodyを取得
        $template = $app['eccube.plugin.mail_magazine.repository.mail_magazine']->find($id);
        if(is_null($template)) {
            // データが存在しない場合はメルマガテンプレート一覧へリダイレクト
            $app->addError('admin.mailmagazine.template.data.notfound', 'admin');

            return $app->redirect($app->url('admin_mail_magazine_template'));
        }

        // プレビューページ表示
        return $app->render('MailMagazine/View/admin/preview.twig', array(
                'Template' => $template
        ));
    }

    /**
     * メルマガテンプレートを論理削除
     * @param Application $app
     * @param Request $request
     * @param unknown $id
     */
    public function delete(Application $app, Request $request, $id)
    {
        // POSTかどうか判定
        // パラメータ$idにマッチするデータが存在するか判定
        // POSTかつ$idに対応するdtb_mailmagazine_templateのレコードがあれば、del_flg = 1に設定して更新
        if ('POST' === $request->getMethod()) {
            // idがからの場合はメルマガテンプレート一覧へリダイレクト
            if(is_null($id) || strlen($id) == 0) {
                $app->addError('admin.mailmagazine.schdule.data.illegalaccess', 'admin');

                return $app->redirect($app->url('admin_mail_magazine_schedule'));
            }

            // メルマガテンプレートを取得する
            $schedule = $app['eccube.plugin.mail_magazine.repository.mail_magazine_schedule']->find($id);

            if(is_null($schedule)) {
                // データが存在しない場合はメルマガテンプレート一覧へリダイレクト
                $app->addError('admin.mailmagazine.schedule.data.notfound', 'admin');

                return $app->redirect($app->url('admin_mail_magazine_schedule'));
            }

            // メルマガテンプレートを削除する
            $app['eccube.plugin.mail_magazine.repository.mail_magazine_schedule']->delete($schedule);

        }

        // メルマガテンプレート一覧へリダイレクト
        return $app->redirect($app->url('admin_mail_magazine_template'));
    }

    /**
     * テンプレート編集画面表示
     * @param Application $app
     * @param Request $request
     * @param unknown $id
     */
    public function edit(Application $app, Request $request, $id) {
dump('render');

        // POST以外はエラーにする
        if ('POST' !== $request->getMethod()) {
            throw new BadRequestHttpException();
        }
dump('render');
        // id の存在確認
        // nullであれば一覧に戻る
        if(is_null($id) || strlen($id) == 0) {
            $app->addError('admin.mailmagazine.schedule.data.illegalaccess', 'admin');

            // メルマガテンプレート一覧へリダイレクト
            return $app->redirect($app->url('admin_mail_magazine_schedule'));
        }

        // 選択したメルマガテンプレートを検索
        // 存在しなければメッセージを表示
        $schedule = $app['eccube.plugin.mail_magazine.repository.mail_magazine_schedule']->find($id);
dump($schedule);

        if(is_null($schedule)) {
            // データが存在しない場合はメルマガテンプレート一覧へリダイレクト
            $app->addError('admin.mailmagazine.schedule.data.notfound', 'admin');

            return $app->redirect($app->url('admin_mail_magazine_schedule'));
        }

        // formの作成
        $form = $app['form.factory']
            ->createBuilder('mail_magazine_schedule', $schedule)
            ->getForm();
dump('render');
        return $app->render('MailMagazine/View/admin/schedule_edit.twig', array(
                'form' => $form->createView()
        ));

    }

    /**
     * テンプレート編集確定処理
     * @param Application $app
     * @param Request $request
     * @param unknown $id
     */
    public function commit(Application $app, Request $request) {

        // Formを取得
        $builder = $app['form.factory']->createBuilder('mail_magazine_schedule');
        $form = $builder->getForm();
        $form->handleRequest($request);
        $data = $form->getData();

        if ('POST' === $request->getMethod()) {

            // 入力項目確認処理を行う.
            // エラーであれば元の画面を表示する
            if (!$form->isValid()) {
                $app->addError("validate error");
                return $app->render('MailMagazine/View/admin/schedule_edit.twig', array(
                        'form' => $form->createView()
                ));
            }

            if(is_null($data['id'])) {
                // =============
                // 登録処理
                // =============
                throw new BadRequestHttpException();

            } else {
                // =============
                // 更新処理
                // =============

                $id = $data['id'];
                // id の存在確認
                // nullであれば一覧に戻る
                if(!$id) {
                    $app->addError('admin.mailmagazine.schedule.data.illegalaccess', 'admin');

                    // スケジュール配信一覧へリダイレクト
                    return $app->redirect($app->url('admin_mail_magazine_schedule'));
                }

                // スケジュール配信設定を取得する
                $schedule = $app['eccube.plugin.mail_magazine.repository.mail_magazine_schedule']->find($id);

                // データが存在しない場合はメルマガテンプレート一覧へリダイレクト
                if(is_null($schedule)) {
                    $app->addError('admin.mailmagazine.schedule.data.notfound', 'admin');

                    return $app->redirect($app->url('admin_mail_magazine_schedule'));
                }
dump($data);
                // 更新処理
                $schedule
                    ->setScheduleName($data['schedule_name'])
                    ->setEnableFlg($data['enable_flg'])
                    ->setSendRepeatFlg($data['sendrepeat_flg'])
                    ->setSendWeek($data['send_week'])
                    ->setSendStart($data['send_start'])
                    ->setSendEnd($data['send_end'])
                    ->setSendTime($data['send_time'])
                    ->setUpdateDate(new \Datetime())
                ;
dump($schedule);
//die();

                $status = $app['eccube.plugin.mail_magazine.repository.mail_magazine_schedule']->update($schedule);
                if (!$status) {
                    $app->addError('admin.mailmagazine.schedule.save.failure', 'admin');
                    return $app->render('MailMagazine/View/admin/schedule_edit.twig', array(
                        'form' => $form->createView()
                    ));
                }

            }

            // 成功時のメッセージを登録する
            $app->addSuccess('admin.mailmagazine.schedule.save.complete', 'admin');

        }

        // スケジュール配信一覧へリダイレクト
        return $app->redirect($app->url('admin_mail_magazine_schedule'));

    }

    /**
     * メルマガテンプレート登録画面を表示する
     * @param Application $app
     * @param Request $request
     */
    public function regist(Application $app, Request $request) {
        $PageLayout = new \Plugin\MailMagazine\Entity\MailMagazineTemplate();

        // formの作成
        $form = $app['form.factory']
            ->createBuilder('mail_magazine_template_edit', $PageLayout)
            ->getForm();

        return $app->render('MailMagazine/View/admin/template_edit.twig', array(
                'form' => $form->createView()
        ));

    }

}
