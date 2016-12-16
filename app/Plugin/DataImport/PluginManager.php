<?php

namespace Plugin\DataImport;

use Eccube\Entity\Master\DeviceType;
use Eccube\Entity\PageLayout;
use Eccube\Plugin\AbstractPluginManager;
use Plugin\DataImport\Entity\DataImportInfo;

/**
 * インストールハンドラー
 * Class PluginManager
 * @package Plugin\DataImport
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

        // ポイント基本設定のデフォルト値を登録
        $DataImportInfo = $app['orm.em']
            ->getRepository('Plugin\DataImport\Entity\DataImportInfo')
            ->getLastInsertData();
        if (is_null($DataImportInfo)) {
            $DataImportInfo = new DataImportInfo();
            $DataImportInfo
                ->setPlgAddDataImportStatus($app['config']['order_deliv'])   // ポイントの確定ステータス：発送済み
                ->setPlgBasicDataImportRate(1)
                ->setPlgDataImportConversionRate(1)
                ->setPlgRoundType(DataImportInfo::POINT_ROUND_CEIL) // 切り上げ
                ->setPlgCalculationType(DataImportInfo::POINT_CALCULATE_NORMAL); // 減算なし

            $app['orm.em']->persist($DataImportInfo);
            $app['orm.em']->flush($DataImportInfo);
        }

        // ページレイアウトにプラグイン使用時の値を代入
        $deviceType = $app['eccube.repository.master.device_type']->findOneById(DeviceType::DEVICE_TYPE_PC);
        $pageLayout = new PageLayout();
        $pageLayout->setDeviceType($deviceType);
        $pageLayout->setFileName('../../Plugin/DataImport/Resource/template/default/dataimport_use');
        $pageLayout->setEditFlg(PageLayout::EDIT_FLG_DEFAULT);
        $pageLayout->setMetaRobots('noindex');
        $pageLayout->setUrl('dataimport_use');
        $pageLayout->setName('商品購入/利用ポイント');
        $app['orm.em']->persist($pageLayout);
        $app['orm.em']->flush($pageLayout);
    }

    /**
     * プラグイン無効化時実行
     * @param $config
     * @param $app
     */
    public function disable($config, $app)
    {
        // ページ情報の削除
        $pageLayout = $app['eccube.repository.page_layout']->findByUrl('dataimport_use');
        foreach ($pageLayout as $deleteNode) {
            $app['orm.em']->remove($deleteNode);
            $app['orm.em']->flush($deleteNode);
        }
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
