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
 *  - 拡張元 : マイページ
 *  - 拡張項目 : 画面表示
 * Class FrontMyPage
 * @package Plugin\DataImport\Event\WorkPlace
 */
class FrontMyPage extends AbstractWorkPlace
{
    /**
     * マイページにデータインポート情報を差し込む
     * @param TemplateEvent $event
     * @return bool
     */
    public function createTwig(TemplateEvent $event)
    {
        $DataImportInfo = $this->app['eccube.plugin.dataimport.repository.dataimportinfo']->getLastInsertData();

        $dataimportRate = $DataImportInfo->getPlgDataImportConversionRate();

        // データインポート計算ヘルパーを取得
        $calculator = $this->app['eccube.plugin.dataimport.calculate.helper.factory'];

        // カスタマー情報を取得
        $Customer = $this->app->user();

        // 計算に必要なエンティティを登録
        $calculator->addEntity('Customer', $Customer);

        // 会員保有データインポートを取得
        $currentDataImport = $calculator->getDataImport();

        // 会員保有データインポート取得判定
        if (empty($currentDataImport)) {
            $currentDataImport = 0;
        }

        // 仮データインポート取得
        $provisionalAddDataImport = $calculator->getProvisionalAddDataImport();

        // 仮データインポート取得判定
        if (empty($provisionalAddDataImport)) {
            $provisionalAddDataImport = 0;
        }

        // データインポート表示用変数作成
        $dataimport = array();
        $dataimport['current'] = $currentDataImport;
        $dataimport['pre'] = $provisionalAddDataImport;
        $dataimport['rate'] = $dataimportRate;

        // 使用データインポートボタン付与
        // twigコードにデータインポート表示欄を追加
        $snippet = $this->app->renderView(
            'DataImport/Resource/template/default/Event/MypageTop/dataimport_box.twig',
            array(
                'dataimport' => $dataimport,
            )
        );
        $search = '<div id="history_list"';
        $this->replaceView($event, $snippet, $search);
    }
}
