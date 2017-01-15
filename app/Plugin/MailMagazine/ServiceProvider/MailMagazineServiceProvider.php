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

namespace Plugin\MailMagazine\ServiceProvider;

use Silex\Application as BaseApplication;
use Silex\ServiceProviderInterface;
use Symfony\Component\Yaml\Yaml;

class MailMagazineServiceProvider implements ServiceProviderInterface
{
    public function register(BaseApplication $app)
    {
        // 不要？
        $app['eccube.plugin.mail_magazine.repository.mail_magazine_plugin'] = $app->share(function () use ($app) {
            return $app['orm.em']->getRepository('Plugin\MailMagazine\Entity\MailMagazinePlugin');
        });

        // メルマガテンプレート用リポジトリ
        $app['eccube.plugin.mail_magazine.repository.mail_magazine'] = $app->share(function () use ($app) {
            return $app['orm.em']->getRepository('Plugin\MailMagazine\Entity\MailMagazineTemplate');
        });

        // 配信履歴用リポジトリ
        $app['eccube.plugin.mail_magazine.repository.mail_magazine_history'] = $app->share(function () use ($app) {
            return $app['orm.em']->getRepository('Plugin\MailMagazine\Entity\MailMagazineSendHistory');
        });

        // スケジュール配信用リポジトリ
        $app['eccube.plugin.mail_magazine.repository.mail_magazine_schedule'] = $app->share(function () use ($app) {
            return $app['orm.em']->getRepository('Plugin\MailMagazine\Entity\MailMagazineSendSchedule');
        });

        // EC-CUBE本体よりコピー
        // Customer用リポジトリ
        $app['eccube.plugin.mail_magazine.repository.mail_magazine_customer'] = $app->share(function () use ($app) {
            return $app['orm.em']->getRepository('Plugin\MailMagazine\Entity\MailMagazineCustomer');
        });
        // SendHistory用リポジトリ
        $app['eccube.plugin.mail_magazine.repository.mail_magazine_send_history'] = $app->share(function () use ($app) {
            return $app['orm.em']->getRepository('Plugin\MailMagazine\Entity\MailMagazineSendHistory');
        });
        // SendSchedule用リポジトリ
        $app['eccube.plugin.mail_magazine.repository.mail_magazine_send_schedule'] = $app->share(function () use ($app) {
            return $app['orm.em']->getRepository('Plugin\MailMagazine\Entity\MailMagazineSendSchedule');
        });
        // SendScheduleComplete用リポジトリ
        $app['eccube.plugin.mail_magazine.repository.mail_magazine_send_schedule_complete'] = $app->share(function () use ($app) {
            return $app['orm.em']->getRepository('Plugin\MailMagazine\Entity\MailMagazineSendScheduleComplete');
        });
        // SendCustomer用リポジトリ
        $app['eccube.plugin.mail_magazine.repository.mail_magazine_send_customer'] = $app->share(function () use ($app) {
            return $app['orm.em']->getRepository('Plugin\MailMagazine\Entity\MailMagazineSendCustomer');
        });

        // 新規会員登録/Myページ
        $app['eccube.plugin.mail_magazine.repository.mail_magazine_mailmaga_customer'] = $app->share(function () use ($app) {
            return $app['orm.em']->getRepository('Plugin\MailMagazine\Entity\MailmagaCustomer');
        });


        // ===========================================
        // 配信内容設定
        // ===========================================
        // 配信設定検索・一覧
        $app->match('/' . $app["config"]["admin_route"] . '/mail', '\\Plugin\\MailMagazine\\Controller\\MailMagazineController::index')
            ->value('id', null)->assert('id', '\d+|')
            ->bind('admin_mail_magazine');

        // 配信内容設定(テンプレ選択)
        $app->match('/' . $app["config"]["admin_route"] . '/mail/select/{id}', '\\Plugin\\MailMagazine\\Controller\\MailMagazineController::select')
            ->value('id', null)->assert('id', '\d+|')
            ->bind('admin_mail_magazine_select');

        // 配信内容編集(テンプレ修正）
        $app->match('/' . $app["config"]["admin_route"] . '/mail', '\\Plugin\\MailMagazine\\Controller\\MailMagazineController::edit')
            ->value('id', null)->assert('id', '\d+|')
            ->bind('admin_mail_magazine_edit');

        // 配信内容確認
        $app->match('/' . $app["config"]["admin_route"] . '/mail/confirm/{id}', '\\Plugin\\MailMagazine\\Controller\\MailMagazineController::confirm')
            ->value('id', null)->assert('id', '\d+|')
            ->bind('admin_mail_magazine_confirm');

        $app->match('/' . $app["config"]["admin_route"] . '/mail/confirm_schedule/{id}', '\\Plugin\\MailMagazine\\Controller\\MailMagazineController::confirm_schedule')
            ->value('id', null)->assert('id', '\d+|')
            ->bind('admin_mail_magazine_confirm_schedule');



        // 配信内容配信
        $app->match('/' . $app["config"]["admin_route"] . '/mail/commit', '\\Plugin\\MailMagazine\\Controller\\MailMagazineController::commit')
            ->value('id', null)->assert('id', '\d+|')
            ->bind('admin_mail_magazine_commit');

        // 配信内容配信
        $app->match('/' . $app["config"]["admin_route"] . '/mail/commit_schedule', '\\Plugin\\MailMagazine\\Controller\\MailMagazineController::commit_schedule')
            ->value('id', null)->assert('id', '\d+|')
            ->bind('admin_mail_magazine_commit_schedule');

        // ===========================================
        // テンプレート設定
        // ===========================================
        // テンプレ一覧
        $app->match('/' . $app["config"]["admin_route"] . '/mail/template', '\\Plugin\\MailMagazine\\Controller\\MailMagazineTemplateController::index')
            ->value('id', null)->assert('id', '\d+|')
            ->bind('admin_mail_magazine_template');

        // テンプレ編集
        $app->match('/' . $app["config"]["admin_route"] . '/mail/template/{id}/edit', '\\Plugin\\MailMagazine\\Controller\\MailMagazineTemplateController::edit')
            ->value('id', null)->assert('id', '\d+|')
            ->bind('admin_mail_magazine_template_edit');

        // テンプレ登録
        $app->match('/' . $app["config"]["admin_route"] . '/mail/template/regist', '\\Plugin\\MailMagazine\\Controller\\MailMagazineTemplateController::regist')
            ->bind('admin_mail_magazine_template_regist');

        // テンプレ編集確定
        $app->match('/' . $app["config"]["admin_route"] . '/mail/template/commit', '\\Plugin\\MailMagazine\\Controller\\MailMagazineTemplateController::commit')
            ->value('id', null)->assert('id', '\d+|')
            ->bind('admin_mail_magazine_template_commit');

        // テンプレ削除
        $app->match('/' . $app["config"]["admin_route"] . '/mail/template/{id}/delete', '\\Plugin\\MailMagazine\\Controller\\MailMagazineTemplateController::delete')
            ->value('id', null)->assert('id', '\d+|')
            ->bind('admin_mail_magazine_template_delete');

        // テンプレプレビュー
        $app->match('/' . $app["config"]["admin_route"] . '/mail/template/{id}/preview', '\\Plugin\\MailMagazine\\Controller\\MailMagazineTemplateController::preview')
            ->value('id', null)->assert('id', '\d+|')
            ->bind('admin_mail_magazine_template_preview');

        // ===========================================
        // 配信履歴
        // ===========================================
        // 配信履歴一覧
        $app->match('/' . $app["config"]["admin_route"] . '/mail/history/{id}', '\\Plugin\\MailMagazine\\Controller\\MailMagazineHistoryController::index')
            ->value('id', null)->assert('id', '\d+|')
            ->bind('admin_mail_magazine_history');

        // 配信履歴一覧プレビュー
        $app->match('/' . $app["config"]["admin_route"] . '/mail/history/{id}/preview', '\\Plugin\\MailMagazine\\Controller\\MailMagazineHistoryController::preview')
            ->value('id', null)->assert('id', '\d+|')
            ->bind('admin_mail_magazine_history_preview');

        // 配信履歴一覧(配信条件)
        $app->match('/' . $app["config"]["admin_route"] . '/mail/history/{id}/condition', '\\Plugin\\MailMagazine\\Controller\\MailMagazineHistoryController::condition')
            ->value('id', null)->assert('id', '\d+|')
            ->bind('admin_mail_magazine_history_condition');

        // 配信履歴一覧削除
        $app->match('/' . $app["config"]["admin_route"] . '/mail/history/{id}/delete', '\\Plugin\\MailMagazine\\Controller\\MailMagazineHistoryController::delete')
            ->value('id', null)->assert('id', '\d+|')
            ->bind('admin_mail_magazine_history_delete');


        $app->match('/' . $app["config"]["admin_route"] . '/mail/schedule/test/{targetdt}', '\\Plugin\\MailMagazine\\Controller\\MailMagazineScheduleController::test')
            ->value('target', null)->assert('id', '\d+|')
            ->bind('admin_mail_magazine_schedule_test');
        // ===========================================
        // スケジュール配信
        // ===========================================
        // スケジュール配信一覧
        $app->match('/' . $app["config"]["admin_route"] . '/mail/schedule', '\\Plugin\\MailMagazine\\Controller\\MailMagazineScheduleController::index')
            ->value('id', null)->assert('id', '\d+|')
            ->bind('admin_mail_magazine_schedule');

        // スケジュール配信編集
        $app->match('/' . $app["config"]["admin_route"] . '/mail/schedule/{id}/edit', '\\Plugin\\MailMagazine\\Controller\\MailMagazineScheduleController::edit')
            ->value('id', null)->assert('id', '\d+|')
            ->bind('admin_mail_magazine_schedule_edit');

        // テンプレ編集確定
        $app->match('/' . $app["config"]["admin_route"] . '/mail/schedule/commit', '\\Plugin\\MailMagazine\\Controller\\MailMagazineScheduleController::commit')
            ->value('id', null)->assert('id', '\d+|')
            ->bind('admin_mail_magazine_schedule_commit');


        // スケジュール配信削除
        $app->match('/' . $app["config"]["admin_route"] . '/mail/schedule/{id}/delete', '\\Plugin\\MailMagazine\\Controller\\MailMagazineScheduleController::delete')
            ->value('id', null)->assert('id', '\d+|')
            ->bind('admin_mail_magazine_schedule_delete');

        // 型登録
        $app['form.types'] = $app->share($app->extend('form.types', function ($types) use ($app) {
                // テンプレート設定
                $types[] = new \Plugin\MailMagazine\Form\Type\MailMagazineTemplateEditType($app);
                $types[] = new \Plugin\MailMagazine\Form\Type\MailMagazineTemplateType($app);

                // 配信内容設定
                $types[] = new \Plugin\MailMagazine\Form\Type\MailMagazineType($app);
                $types[] = new \Plugin\MailMagazine\Form\Type\MailMagazineProductType($app);

                $types[] = new \Plugin\MailMagazine\Form\Type\MailMagazineScheduleType($app);
            return $types;
        }));

        // Form Extension
        $app['form.type.extensions'] = $app->share($app->extend('form.type.extensions', function ($extensions) use ($app) {
            $extensions[] = new \Plugin\MailMagazine\Form\Extension\EntryMailMagazineTypeExtension($app);
            $extensions[] = new \Plugin\MailMagazine\Form\Extension\CustomerMailMagazineTypeExtension($app);
            return $extensions;
        }));

        // -----------------------------
        // サービス
        // -----------------------------
        $app['eccube.plugin.mail_magazine.service.mail'] = $app->share(function () use ($app) {
                return new \Plugin\MailMagazine\Service\MailMagazineService($app);
            });

        // -----------------------------
        // メッセージ登録
        // -----------------------------
        $app['translator'] = $app->share($app->extend('translator', function ($translator, \Silex\Application $app) {
            $translator->addLoader('yaml', new \Symfony\Component\Translation\Loader\YamlFileLoader());

            $file = __DIR__ . '/../Resource/locale/message.' . $app['locale'] . '.yml';
            if (file_exists($file)) {
                $translator->addResource('yaml', $file, $app['locale']);
            }

            return $translator;
        }));

        // メニュー登録
        $app['config'] = $app->share($app->extend('config', function ($config) {
            $addNavi = array(
                'id' => 'mailmagazine',
                'name' => "メルマガ管理",
                'has_child' => true,
                'icon' => 'cb-comment',
                'child' => array(
                    array(
                        'id' => "mailmagazine",
                        'name' => "配信内容設定",
                        'url' => "admin_mail_magazine",
                    ),
                    array(
                        'id' => "mailmagazine_schedule",
                        'name' => "スケジュール配信管理",
                        'url' => "admin_mail_magazine_schedule",
                    ),
                    array(
                        'id' => "mailmagazine_template",
                        'name' => "テンプレート設定",
                        'url' => "admin_mail_magazine_template",
                    ),
                    array(
                        'id' => "mailmagazine_history",
                        'name' => "配信履歴",
                        'url' => "admin_mail_magazine_history",
                    ),
                ),
            );

            $nav = $config['nav'];
            foreach ($nav as $key => $val) {
                if ("setting" == $val['id']) {
                    array_splice($nav, $key, 0, array($addNavi));
                    break;
                }
            }
            $config['nav'] = $nav;
            return $config;
        }));
    }

    public function boot(BaseApplication $app)
    {
    }
}
