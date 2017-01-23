<?php

namespace Plugin\DownloadProduct;

use Eccube\Entity\Master\DeviceType;
use Eccube\Entity\PageLayout;
use Eccube\Plugin\AbstractPluginManager;
use Plugin\DownloadProduct\Entity\DownloadProductInfo;

/**
 * インストールハンドラー
 * Class PluginManager
 * @package Plugin\DownloadProduct
 */
class PluginManager extends AbstractPluginManager
{
    /**
     * PluginManager constructor.
     */
    public function __construct()
    {
    }

    /**
     * インストール時に実行
     * @param $config
     * @param $app
     */
    public function install($config, $app)
    {
    }

    /**
     * アンインストール時に実行
     * @param $config
     * @param $app
     */
    public function uninstall($config, $app)
    {
        $this->migrationSchema($app, __DIR__.'/Resource/doctrine/migration', $config['code'], 0);
    }

    /**
     * プラグイン有効化時に実行
     * @param $config
     * @param $app
     */
    public function enable($config, $app)
    {
        $this->migrationSchema($app, __DIR__.'/Resource/doctrine/migration', $config['code']);


    }

    /**
     * プラグイン無効化時実行
     * @param $config
     * @param $app
     */
    public function disable($config, $app)
    {
        //$this->migrationSchema($app, __DIR__.'/Resource/doctrine/migration', $config['code'], 0);

    }

    /**
     * アップデート時に行う処理
     * @param $config
     * @param $app
     */
    public function update($config, $app)
    {
    }
}
