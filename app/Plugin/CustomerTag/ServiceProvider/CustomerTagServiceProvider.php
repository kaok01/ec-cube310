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

namespace Plugin\CustomerTag\ServiceProvider;

use Silex\Application as BaseApplication;
use Silex\ServiceProviderInterface;

class CustomerTagServiceProvider implements ServiceProviderInterface
{
    public function register(BaseApplication $app)
    {

        // 不要？
        $app['eccube.plugin.customertag.repository.customertag_plugin'] = $app->share(function () use ($app) {
            return $app['orm.em']->getRepository('Plugin\CustomerTag\Entity\CustomerTagPlugin');
        });

        // メーカーテーブル用リポジトリ
        $app['eccube.plugin.customertag.repository.customertag'] = $app->share(function () use ($app) {
            return $app['orm.em']->getRepository('Plugin\CustomerTag\Entity\CustomerTag');
        });

        $app['eccube.plugin.customertag.repository.customer_customertag'] = $app->share(function () use ($app) {
            return $app['orm.em']->getRepository('Plugin\CustomerTag\Entity\CustomerCustomerTag');
        });

        // 一覧・登録・修正
        $app->match('/' . $app["config"]["admin_route"] . '/customer/customertag/{id}', '\\Plugin\\CustomerTag\\Controller\\CustomerTagController::index')
            ->value('id', null)->assert('id', '\d+|')
            ->bind('admin_customertag');

        // 削除
        $app->match('/' . $app["config"]["admin_route"] . '/customer/customertag/{id}/delete', '\\Plugin\\CustomerTag\\Controller\\CustomerTagController::delete')
            ->value('id', null)->assert('id', '\d+|')
            ->bind('admin_customertag_delete');

        // 上
        $app->match('/' . $app["config"]["admin_route"] . '/customer/customertag/{id}/up', '\\Plugin\\CustomerTag\\Controller\\CustomerTagController::up')
            ->value('id', null)->assert('id', '\d+|')
            ->bind('admin_customertag_up');

        // 下
        $app->match('/' . $app["config"]["admin_route"] . '/customer/customertag/{id}/down', '\\Plugin\\CustomerTag\\Controller\\CustomerTagController::down')
            ->value('id', null)->assert('id', '\d+|')
            ->bind('admin_customertag_down');

        // 型登録
        $app['form.types'] = $app->share($app->extend('form.types', function ($types) use ($app) {
            $types[] = new \Plugin\CustomerTag\Form\Type\CustomerTagType($app);
            return $types;
        }));

        // Form Extension
        $app['form.type.extensions'] = $app->share($app->extend('form.type.extensions', function ($extensions) use ($app) {
            $extensions[] = new \Plugin\CustomerTag\Form\Extension\Admin\CustomerCustomerTagTypeExtension($app);
            return $extensions;
        }));

        // メッセージ登録
        $app['translator'] = $app->share($app->extend('translator', function ($translator, \Silex\Application $app) {
            $translator->addLoader('yaml', new \Symfony\Component\Translation\Loader\YamlFileLoader());

            $file = __DIR__ . '/../Resource/locale/message.' . $app['locale'] . '.yml';
            if (file_exists($file)) {
                $translator->addResource('yaml', $file, $app['locale']);
            }

            return $translator;
        }));
        /**
         * 
         */
        $app['eccube.plugin.customertag.service'] = $app->share(
            function () use ($app) {
                return new \Plugin\CustomerTag\Service\CustomerTagService($app);
            }
        );

        // メニュー登録
        $app['config'] = $app->share($app->extend('config', function ($config) {
            $addNavi['id'] = "customertag";
            $addNavi['name'] = "会員情報タグ管理";
            $addNavi['url'] = "admin_customertag";

            $nav = $config['nav'];
            foreach ($nav as $key => $val) {
                if ("customer" == $val["id"]) {
                    $nav[$key]['child'][] = $addNavi;
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
