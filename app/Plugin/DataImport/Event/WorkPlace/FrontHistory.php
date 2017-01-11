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
namespace Plugin\DataImport\Event\WorkPlace;

use Eccube\Event\TemplateEvent;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * フックデータインポート汎用処理具象クラス
 *  - 拡張元 : マイページ履歴表示
 *  - 拡張項目 : 画面表示
 * Class FrontHistory
 * @package Plugin\DataImport\Event\WorkPlace
 */
class FrontHistory extends AbstractWorkPlace
{
    /**
     * 履歴情報挿入
     * @param TemplateEvent $event
     * @return bool
     */
    public function createTwig(TemplateEvent $event)
    {
        // 必要情報の取得と判定
        $parameters = $event->getParameters();
        if (!isset($parameters['Order']) || empty($parameters['Order'])) {
            return false;
        }

        if (is_null($parameters['Order']->getCustomer())) {
            return false;
        }

        // データインポート計算ヘルパーを取得
        $calculator = null;
        $calculator = $this->app['eccube.plugin.dataimport.calculate.helper.factory'];

        // ヘルパーの取得判定
        if (empty($calculator)) {
            return false;
        }

        // 利用データインポートの取得と設定
        $useDataImport = $this->app['eccube.plugin.dataimport.repository.dataimport']->getLatestUseDataImport($parameters['Order']);
        $useDataImport = abs($useDataImport);

        // 計算に必要なエンティティを登録
        $calculator->addEntity('Order', $parameters['Order']);
        $calculator->addEntity('Customer', $parameters['Order']->getCustomer());
        $calculator->setUseDataImport($useDataImport);

        // 付与データインポート取得
        $addDataImport = $this->app['eccube.plugin.dataimport.repository.dataimport']->getLatestAddDataImportByOrder($parameters['Order']);

        // 付与データインポート取得判定
        if (empty($addDataImport)) {
            $addDataImport = 0;
        }

        // データインポート表示用変数作成
        $dataimport = array();

        // エラー判定
        // false が返却された際は、利用データインポート値が保有データインポート値を超えている
        $dataimport['add'] = $addDataImport;

        // Twigデータ内IDをキーに表示項目を追加
        // データインポート情報表示
        // false が返却された際は、利用データインポート値が保有データインポート値を超えている
        $dataimport['use'] = $useDataImport;
        $snippet = $this->app->renderView(
            'DataImport/Resource/template/default/Event/History/dataimport_summary.twig',
            array(
                'dataimport' => $dataimport,
            )
        );


        $search = '<p id="summary_box__payment_total"';
        $this->replaceView($event, $snippet, $search);

    }
}
