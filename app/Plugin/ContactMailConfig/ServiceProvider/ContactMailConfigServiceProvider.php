<?php
/*
 * This file is part of EC-CUBE
 *
 * Copyright(c) LOCKON CO.,LTD. All Rights Reserved.
 * http://www.lockon.co.jp/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Plugin\ContactMailConfig\ServiceProvider;

use Eccube\Application;
use Eccube\Common\Constant;
use Plugin\ContactMailConfig\Form\Type\MailConfigType;
use Silex\Application as BaseApplication;
use Silex\ServiceProviderInterface;

class ContactMailConfigServiceProvider implements ServiceProviderInterface
{
    public function register(BaseApplication $app)
    {
        //Repository
        $app['eccube.repository.plugin.ContactMailConfig'] = $app->share(function () use ($app) {
            return $app['orm.em']->getRepository('Plugin\ContactMailConfig\Entity\ContactMailConfig');
        });

        // Route
        $app->match('/' . $app["config"]["admin_route"] . '/plugin/mail_config', '\\Plugin\\ContactMailConfig\\Controller\\MainController::index')
            ->bind('plugin_ContactMailConfig_config');

        // フォーム
        $app['form.types'] = $app->share($app->extend('form.types', function ($types) use ($app) {
            $types[] = new MailConfigType();

            return $types;
        }));

        // メニュー
        $app['config'] = $app->share($app->extend('config', function ($config) {
            $addNavi = array(
                'id' => 'mail_config',
                'name' => "メール送信設定",
                'has_child' => true,
                'icon' => 'cb-comment',
                'child' => array(
                    array(
                        'id' => "plugin_ContactMailConfig_index",
                        'name' => "お問い合わせメール",
                        'url' => "plugin_ContactMailConfig_config",
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
