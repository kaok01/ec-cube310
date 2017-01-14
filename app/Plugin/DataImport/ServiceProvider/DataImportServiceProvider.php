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
namespace Plugin\DataImport\ServiceProvider;

use Eccube\Application;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\FingersCrossed\ErrorLevelActivationStrategy;
use Monolog\Handler\FingersCrossedHandler;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Processor\IntrospectionProcessor;
use Monolog\Processor\ProcessIdProcessor;
use Monolog\Processor\WebProcessor;
use Plugin\DataImport\Helper\MailHelper;
use Plugin\DataImport\Helper\DataImportCalculateHelper\DataImportCalculateHelper;
use Plugin\DataImport\Helper\DataImportHistoryHelper\DataImportHistoryHelper;
use Silex\Application as BaseApplication;
use Silex\ServiceProviderInterface;
use Symfony\Bridge\Monolog\Logger;

/**
 * Class DataImportServiceProvider
 * @package Plugin\DataImport\ServiceProvider
 */
class DataImportServiceProvider implements ServiceProviderInterface
{
    /**
     * サービス登録処理
     * @param BaseApplication $app
     */
    public function register(BaseApplication $app)
    {
        /**
         * ルーティング登録
         * 管理画面 > 設定 > 基本情報設定 > データインポート基本情報設定画面
         */
        $app->match(
            '/'.$app['config']['admin_route'].'/dataimport/setting',
            'Plugin\DataImport\Controller\AdminDataImportController::index'
        )->bind('dataimport_info');

 

        /**
         * レポジトリ登録
         */
        $app['eccube.plugin.dataimport.repository.dataimport'] = $app->share(
            function () use ($app) {
                return $app['orm.em']->getRepository('Plugin\DataImport\Entity\DataImport');
            }
        );

        /** データインポートステータステーブル用リポジトリ */
        $app['eccube.plugin.dataimport.repository.dataimportstatus'] = $app->share(
            function () use ($app) {
                return $app['orm.em']->getRepository('Plugin\DataImport\Entity\DataImportStatus');
            }
        );


        /** データインポート機能基本情報テーブル用リポジトリ */
        $app['eccube.plugin.dataimport.repository.dataimportinfo'] = $app->share(
            function () use ($app) {
                return $app['orm.em']->getRepository('Plugin\DataImport\Entity\DataImportInfo');
            }
        );

        /** データインポート会員情報テーブル */
        $app['eccube.plugin.dataimport.repository.dataimportcustomer'] = $app->share(
            function () use ($app) {
                return $app['orm.em']->getRepository('Plugin\DataImport\Entity\DataImportCustomer');
            }
        );


        $app['eccube.plugin.dataimport.repository.dataimportorder'] = $app->share(
            function () use ($app) {
                return $app['orm.em']->getRepository('Plugin\DataImport\Entity\DataImportOrder');
            }
        );
        /**
         * ルーティング登録
         * 管理画面 > 会員管理管理 > 会員CSV登録
         */
        $app->match(
            '/'.$app['config']['admin_route'].'/customer/csvimport',
            'Plugin\DataImport\Controller\Admin\Customer\CsvImportController::csvCustomer'
        )->bind('admin_dataimport_customer_csv_import');

        /**
         * ルーティング登録
         * 管理画面 > 受注管理 > 受注CSV登録
         */
        $app->match(
            '/'.$app['config']['admin_route'].'/order/csvimport',
            'Plugin\DataImport\Controller\Admin\Order\CsvImportController::csvOrder'
        )->bind('admin_dataimport_order_csv_import');

        $app->match(
            '/'.$app['config']['admin_route'].'/order/infotopcsvimport',
            'Plugin\DataImport\Controller\Admin\Order\CsvImportController::csvOrder'
        )->bind('admin_dataimport_order_infotopcsv_import');

        /**
         * ルーティング登録
         * CSVファイル取得
         */
        $app->match(
            '/'.$app['config']['admin_route'].'/dataimport/csv_template/{type}', 
            'Plugin\DataImport\Controller\Base\CsvImportController::csvTemplate'
        )->bind('admin_dataimport_csv_template');

        // /**
        //  * ルーティング登録
        //  * CSVファイル取得
        //  */
        // $app->match(
        //     '/'.$app['config']['admin_route'].'/order/productmap', 
        //     'Plugin\DataImport\Controller\Admin\Order\CsvImportController::csvOrder'
        // )->bind('admin_dataimport_order_');

        // 商品関連付け情報テーブルリポジトリ
        $app['eccube.plugin.dataimport.repository.productmap_product'] = $app->share(function () use ($app) {
            return $app['orm.em']->getRepository('Plugin\DataImport\Entity\ProductMapProduct');
        });

        // 商品関連付けの一覧
        $app->match('/' . $app["config"]["admin_route"] . '/dataimport/productmap/list', '\Plugin\DataImport\Controller\Admin\Order\ProductMapController::index')
            ->value('id', null)->assert('id', '\d+|')
            ->bind('admin_dataimport_productmap_list');

        // 商品関連付けの新規先
        $app->match('/' . $app["config"]["admin_route"] . '/dataimport/productmap/new', '\Plugin\DataImport\Controller\Admin\Order\ProductMapController::create')
            ->value('id', null)->assert('id', '\d+|')
            ->bind('admin_dataimport_productmap_new');

        // 商品関連付けの新規作成・編集確定
        $app->match('/' . $app["config"]["admin_route"] . '/dataimport/productmap/commit', '\Plugin\DataImport\Controller\Admin\Order\ProductMapController::commit')
        ->value('id', null)->assert('id', '\d+|')
        ->bind('admin_dataimport_productmap_commit');

        // 商品関連付けの編集
        $app->match('/' . $app["config"]["admin_route"] . '/dataimport/productmap/edit/{id}', '\Plugin\DataImport\Controller\Admin\Order\ProductMapController::edit')
            ->value('id', null)->assert('id', '\d+|')
            ->bind('admin_dataimport_productmap_edit');

        // 商品関連付けの削除
        $app->match('/' . $app["config"]["admin_route"] . '/dataimport/productmap/delete/{id}', '\Plugin\DataImport\Controller\Admin\Order\ProductMapController::delete')
        ->value('id', null)->assert('id', '\d+|')
        ->bind('admin_dataimport_productmap_delete');


        // 商品検索画面表示
        $app->post('/' . $app["config"]["admin_route"] . '/dataimport/search/product', '\Plugin\DataImport\Controller\Admin\Order\ProductMapSearchModelController::searchProduct')
            ->bind('admin_dataimport_search_product');



        // サービスの登録
        $app['eccube.plugin.dataimport.service.productmap'] = $app->share(function () use ($app) {
            return new \Plugin\DataImport\Service\ProductMapService($app);
        });


        /**
         * フォームタイプ登録
         */
        $app['form.types'] = $app->share($app->extend('form.types', function ($types) use ($app) {
            $types[] = new \Plugin\DataImport\Form\Type\DataImportInfoType($app);
            $types[] = new \Plugin\DataImport\Form\Type\ProductMapProductType($app);
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
                    $addNavi['id'] = "dataimport_info";
                    $addNavi['name'] = "DataImport設定";
                    $addNavi['url'] = "dataimport_info";
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
        $app['config'] = $app->share($app->extend('config', function ($config) {
            $addNavi['id'] = "admin_dataimport_order_csv_import";
            $addNavi['name'] = "受注CSV登録";
            $addNavi['url'] = "admin_dataimport_order_csv_import";

            $nav = $config['nav'];
            foreach ($nav as $key => $val) {
                if ("order" == $val["id"]) {
                    $nav[$key]['child'][] = $addNavi;
                }
            }
            $config['nav'] = $nav;

            return $config;
        }));
        /*
        $app['config'] = $app->share($app->extend('config', function ($config) {
            $addNavi['id'] = "admin_dataimport_order_infotopcsv_import";
            $addNavi['name'] = "CSV取込み（開発用）";
            $addNavi['url'] = "admin_dataimport_order_infotopcsv_import";

            $nav = $config['nav'];
            foreach ($nav as $key => $val) {
                if ("order" == $val["id"]) {
                    $nav[$key]['child'][] = $addNavi;
                }
            }
            $config['nav'] = $nav;

            return $config;
        }));
        */
        $app['config'] = $app->share($app->extend('config', function ($config) {
            $addNavi['id'] = "admin_dataimport_customer_csv_import";
            $addNavi['name'] = "会員CSV登録";
            $addNavi['url'] = "admin_dataimport_customer_csv_import";

            $nav = $config['nav'];
            foreach ($nav as $key => $val) {
                if ("customer" == $val["id"]) {
                    $nav[$key]['child'][] = $addNavi;
                }
            }
            $config['nav'] = $nav;

            return $config;
        }));
        $app['config'] = $app->share($app->extend('config', function ($config) {
            $addNavi['id'] = "admin_dataimport_productmap_list";
            $addNavi['name'] = "商品ID関連付け";
            $addNavi['url'] = "admin_dataimport_productmap_list";

            $nav = $config['nav'];
            foreach ($nav as $key => $val) {
                if ("order" == $val["id"]) {
                    $nav[$key]['child'][] = $addNavi;
                }
            }
            $config['nav'] = $nav;

            return $config;
        }));
 

        if(isset($app['eccube.plugin.customertag.service'])){

            /**
             * ルーティング登録
             * 管理画面 > 会員管理管理 > 会員CSV登録
             */
            $app->match(
                '/'.$app['config']['admin_route'].'/customer/customertag_csvimport',
                'Plugin\DataImport\Controller\Admin\Customer\CustomerTagController::csvCustomerTag'
            )->bind('admin_dataimport_customertag_csv_import');

            $app->match(
                '/'.$app['config']['admin_route'].'/customer/customertag_csvexport',
                'Plugin\DataImport\Controller\Admin\Customer\CustomerTagController::export'
            )->bind('admin_dataimport_customertag_csv_export');

            $app['config'] = $app->share($app->extend('config', function ($config) {
                $addNavi['id'] = "admin_dataimport_customertag_csv_import";
                $addNavi['name'] = "会員情報タグCSV登録";
                $addNavi['url'] = "admin_dataimport_customertag_csv_import";

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
        $app['monolog.dataimport'] = $this->initLogger($app, 'dataimport');

        // ログファイル管理画面用設定
        $app['monolog.dataimport.admin'] = $this->initLogger($app, 'dataimport_admin');

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
     * データインポートプラグイン用ログファイルの初期設定
     *
     * @param BaseApplication $app
     * @param $logFileName
     * @return \Closure
     */
    protected function initLogger(BaseApplication $app, $logFileName)
    {

        return $app->share(function ($app) use ($logFileName) {
            $logger = new $app['monolog.logger.class']('plugin.dataimport');
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
