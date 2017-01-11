<?php

namespace Plugin\DataImport\Tests\Util;

/**
 * データインポートテストケースのユーティリティ.
 *
 * 何故か abstract class を作るとテストに失敗するため、汎用的なメソッドを static で提供する.
 *
 * @author Kentaro Ohkouchi
 */
class DataImportTestUtil {

    /**
     * 会員の保有データインポートを返す.
     *
     * @see Plugin\DataImport\Event\WorkPlace\FrontShoppingComplete::calculateCurrentDataImport()
     */
    public static function calculateCurrentDataImport($Customer, $app)
    {
        $orderIds = $app['eccube.plugin.dataimport.repository.dataimportstatus']->selectOrderIdsWithFixedByCustomer(
            $Customer->getId()
        );
        $calculateCurrentDataImport = $app['eccube.plugin.dataimport.repository.dataimport']->calcCurrentDataImport(
            $Customer->getId(),
            $orderIds
        );
        return $calculateCurrentDataImport;
    }

    /**
     * 会員の保有データインポートを設定する.
     */
    public static function saveCustomerDataImport($Customer, $currentDataImport, $app)
    {
        // 手動設定データインポートを登録
        $app['eccube.plugin.dataimport.history.service']->refreshEntity();
        $app['eccube.plugin.dataimport.history.service']->addEntity($Customer);
        $app['eccube.plugin.dataimport.history.service']->saveManualdataimport($currentDataImport);
        $dataimport = array();
        $dataimport['current'] = $currentDataImport;
        $dataimport['use'] = 0;
        $dataimport['add'] = $currentDataImport;

        // 手動設定データインポートのスナップショット登録
        $app['eccube.plugin.dataimport.history.service']->refreshEntity();
        $app['eccube.plugin.dataimport.history.service']->addEntity($Customer);
        $app['eccube.plugin.dataimport.history.service']->saveSnapShot($dataimport);
        // 保有データインポートを登録
        $app['eccube.plugin.dataimport.repository.dataimportcustomer']->saveDataImport(
            $currentDataImport,
            $Customer
        );
    }
}
