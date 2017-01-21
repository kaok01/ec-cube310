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
namespace Plugin\DownloadProduct\ServiceProvider;

use Eccube\Application;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\FingersCrossed\ErrorLevelActivationStrategy;
use Monolog\Handler\FingersCrossedHandler;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Processor\IntrospectionProcessor;
use Monolog\Processor\ProcessIdProcessor;
use Monolog\Processor\WebProcessor;
use Plugin\DownloadProduct\Helper\MailHelper;
use Plugin\DownloadProduct\Helper\DownloadProductCalculateHelper\DownloadProductCalculateHelper;
use Plugin\DownloadProduct\Helper\DownloadProductHistoryHelper\DownloadProductHistoryHelper;
use Silex\Application as BaseApplication;
use Silex\ServiceProviderInterface;
use Symfony\Bridge\Monolog\Logger;

/**
 * Class DownloadProductServiceProvider
 * @package Plugin\DownloadProduct\ServiceProvider
 */
class DownloadProductServiceProvider implements ServiceProviderInterface
{
    /**
     * サービス登録処理
     * @param BaseApplication $app
     */
    public function register(BaseApplication $app)
    {
        /**
         * ルーティング登録
         * 管理画面 > 設定 > 基本情報設定 > ダウンロード商品基本情報設定画面
         */
        $app->match(
            '/'.$app['config']['admin_route'].'/downloadproduct/setting',
            'Plugin\DownloadProduct\Controller\AdminDownloadProductController::index'
        )->bind('downloadproduct_info');


        /**
         * ルーティング登録
         * 管理画面 > 商品一覧 > メニュー > ダウンロード商品管理
         */
        $app->match(
            '/'.$app['config']['admin_route'].'/downloadproduct/product_download/{id}',
            'Plugin\DownloadProduct\Controller\Admin\Product\DownloadController::index'
        )->bind('admin_downloadproduct_product_download');

        // ダウンロード商品ファイル新規
        $app->match('/' . $app["config"]["admin_route"] . '/downloadproduct/product_download/new', '\Plugin\DownloadProduct\Controller\Admin\Product\DownloadController::create')
            ->value('id', null)->assert('id', '\d+|')
            ->bind('admin_downloadproduct_product_download_new');

        // ダウンロード商品新規ファイル編集確定
        $app->match('/' . $app["config"]["admin_route"] . '/downloadproduct/product_download/commit', '\Plugin\DownloadProduct\Controller\Admin\Product\DownloadController::commit')
        ->value('id', null)->assert('id', '\d+|')
        ->bind('admin_downloadproduct_product_download_commit');

        // ダウンロード商品ファイル編集
        $app->match('/' . $app["config"]["admin_route"] . '/downloadproduct/product_download/edit/{id}', '\Plugin\DownloadProduct\Controller\Admin\Product\DownloadController::edit')
            ->value('id', null)->assert('id', '\d+|')
            ->bind('admin_downloadproduct_product_download_edit');

        // ダウンロード商品ファイル削除
        $app->match('/' . $app["config"]["admin_route"] . '/downloadproduct/product_download/delete/{id}', '\Plugin\DownloadProduct\Controller\Admin\Product\DownloadController::delete')
        ->value('id', null)->assert('id', '\d+|')
        ->bind('admin_downloadproduct_product_download_delete');


        /**
         * ルーティング登録
         * 管理画面 > 受注一覧 >　メニュー > ダウンロード商品リンク管理
         */
        $app->match(
            '/'.$app['config']['admin_route'].'/downloadproduct/order_downloadlink/{id}',
            'Plugin\DownloadProduct\Controller\Admin\Order\DownloadLinkController::index'
        )->bind('admin_downloadproduct_order_downloadlink');

        // ダウンロード商品リンク通知
        $app->match('/' . $app["config"]["admin_route"] . '/downloadproduct/order_downloadlink/new', '\Plugin\DownloadProduct\Controller\Admin\Order\DownloadLinkController::notify')
            ->value('id', null)->assert('id', '\d+|')
            ->bind('admin_downloadproduct_order_downloadlink_notify');

        // ダウンロード商品リンク編集
        $app->match('/' . $app["config"]["admin_route"] . '/downloadproduct/order_downloadlink/commit', '\Plugin\DownloadProduct\Controller\Admin\Order\DownloadLinkController::commit')
        ->value('id', null)->assert('id', '\d+|')
        ->bind('admin_downloadproduct_order_downloadlink_commit');

        // ダウンロード商品リンク削除
        $app->match('/' . $app["config"]["admin_route"] . '/downloadproduct/order_downloadlink/edit/{id}', '\Plugin\DownloadProduct\Controller\Admin\Order\DownloadLinkController::edit')
            ->value('id', null)->assert('id', '\d+|')
            ->bind('admin_downloadproduct_order_downloadlink_edit');

        // ダウンロード商品ファイル削除
        $app->match('/' . $app["config"]["admin_route"] . '/downloadproduct/order_downloadlink/delete/{id}', '\Plugin\DownloadProduct\Controller\Admin\Order\DownloadLinkController::delete')
        ->value('id', null)->assert('id', '\d+|')
        ->bind('admin_downloadproduct_order_downloadlink_delete');

        /**
         * ルーティング登録
         * Mypage >　注文履歴 >  ダウンロード商品リンク
         */
        $app->match(
            '/Mypage/downloadproduct/orderdownloadlink/{id}',
            'Plugin\DownloadProduct\Controller\Front\Mypage\OrderDownloadLinkController::index'
        )->bind('front_downloadproduct_order_downloadlink');

        // ダウンロード商品ファイル取得
        $app->match(
            '/Mypage/downloadproduct/orderdownloadlink/download/{downloadlink}',
            'Plugin\DownloadProduct\Controller\Front\Mypage\OrderDownloadLinkController::download'
            )
            ->value('downloadlink', null)->assert('id', '\d+|')
            ->bind('front_downloadproduct_order_downloadlink_dowload');

        // 通知メールのダウンロード商品ファイル取得
        $app->match(
            '/download/{downloadlink}',
            'Plugin\DownloadProduct\Controller\Front\Mypage\OrderDownloadLinkController::notifylink'
            )
            ->value('downloadlink', null)->assert('id', '\d+|')
            ->bind('front_downloadproduct_order_downloadlink_notifylink');

 
 

        /**
         * レポジトリ登録
         */
        $app['eccube.plugin.downloadproduct.repository.downloadproduct'] = $app->share(
            function () use ($app) {
                return $app['orm.em']->getRepository('Plugin\DownloadProduct\Entity\DownloadProduct');
            }
        );
        $app['eccube.plugin.downloadproduct.repository.productdownload'] = $app->share(
            function () use ($app) {
                return $app['orm.em']->getRepository('Plugin\DownloadProduct\Entity\ProductDownload');
            }
        );
        $app['eccube.plugin.downloadproduct.repository.orderdownloadlink'] = $app->share(
            function () use ($app) {
                return $app['orm.em']->getRepository('Plugin\DownloadProduct\Entity\OrderDownloadLink');
            }
        );


        // サービスの登録
        $app['eccube.plugin.downloadproduct.service.download'] = $app->share(function () use ($app) {
            return new \Plugin\DownloadProduct\Service\DownloadService($app);
        });


        /**
         * フォームタイプ登録
         */
        $app['form.types'] = $app->share($app->extend('form.types', function ($types) use ($app) {
            $types[] = new \Plugin\DownloadProduct\Form\Type\DownloadProductInfoType($app);
            $types[] = new \Plugin\DownloadProduct\Form\Type\ProductMapProductType($app);
            $types[] = new \Plugin\DownloadProduct\Form\Type\DownloadType($app);
            $types[] = new \Plugin\DownloadProduct\Form\Type\DownloadLinkType($app);
            return $types;
        })
        );

        /**
         * メニュー登録
         */
        $app['config'] = $app->share(
            $app->extend(
                'config',
                function ($config) {
                    $addNavi['id'] = "downloadproduct_info";
                    $addNavi['name'] = "DownloadProduct設定";
                    $addNavi['url'] = "downloadproduct_info";
                    $nav = $config['nav'];
                    foreach ($nav as $key => $val) {
                        if ("setting" == $val["id"]) {
                            $nav[$key]['child'][0]['child'][] = $addNavi;
                        }
                    }
                    $config['nav'] = $nav;

                    return $config;
                }
            )
        );


        /**
         * メッセージ登録
         */
        $app['translator'] = $app->share(
            $app->extend(
                'translator',
                function ($translator, \Silex\Application $app) {
                    $translator->addLoader('yaml', new \Symfony\Component\Translation\Loader\YamlFileLoader());
                    $file = __DIR__.'/../Resource/locale/message.'.$app['locale'].'.yml';
                    if (file_exists($file)) {
                        $translator->addResource('yaml', $file, $app['locale']);
                    }

                    return $translator;
                }
            )
        );

        // ログファイル設定
        $app['monolog.downloadproduct'] = $this->initLogger($app, 'downloadproduct');

        // ログファイル管理画面用設定
        $app['monolog.downloadproduct.admin'] = $this->initLogger($app, 'downloadproduct_admin');

    }

    /**
     * 初期化時処理
     *  - 本クラスでは使用せず
     * @param BaseApplication $app
     */
    public function boot(BaseApplication $app)
    {
    }

    /**
     * ダウンロード商品プラグイン用ログファイルの初期設定
     *
     * @param BaseApplication $app
     * @param $logFileName
     * @return \Closure
     */
    protected function initLogger(BaseApplication $app, $logFileName)
    {

        return $app->share(function ($app) use ($logFileName) {
            $logger = new $app['monolog.logger.class']('plugin.downloadproduct');
            $file = $app['config']['root_dir'].'/app/log/'.$logFileName.'.log';
            $RotateHandler = new RotatingFileHandler($file, $app['config']['log']['max_files'], Logger::INFO);
            $RotateHandler->setFilenameFormat(
                $logFileName.'_{date}',
                'Y-m-d'
            );

            $token = substr($app['session']->getId(), 0, 8);
            $format = "[%datetime%] [".$token."] %channel%.%level_name%: %message% %context% %extra%\n";
            // $RotateHandler->setFormatter(new LineFormatter($format, null, false, true));
            $RotateHandler->setFormatter(new LineFormatter($format));

            $logger->pushHandler(
                new FingersCrossedHandler(
                    $RotateHandler,
                    new ErrorLevelActivationStrategy(Logger::INFO)
                )
            );

            $logger->pushProcessor(function ($record) {
                // 出力ログからファイル名を削除し、lineを最終項目にセットしなおす
                unset($record['extra']['file']);
                $line = $record['extra']['line'];
                unset($record['extra']['line']);
                $record['extra']['line'] = $line;

                return $record;
            });

            $ip = new IntrospectionProcessor();
            $logger->pushProcessor($ip);

            $web = new WebProcessor();
            $logger->pushProcessor($web);

            // $uid = new UidProcessor(8);
            // $logger->pushProcessor($uid);

            $process = new ProcessIdProcessor();
            $logger->pushProcessor($process);


            return $logger;
        });

    }


}
