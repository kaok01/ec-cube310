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
 * フックポイント汎用処理具象クラス
 *  - 拡張元 : 商品購入確認
 *  - 拡張項目 : 合計金額・ポイント
 * Class FrontShopping
 * @package Plugin\DataImport\Event\WorkPlace
 */
class FrontShopping extends AbstractWorkPlace
{
    /**
     * フロント商品購入確認画面
     * - ポイント計算/購入金額合計計算
     * @param TemplateEvent $event
     * @return bool
     */
    public function createTwig(TemplateEvent $event)
    {
        $args = $event->getParameters();

        $Order = $args['Order'];
        $Customer = $Order->getCustomer();

        // ポイント利用画面で入力された利用ポイントを取得
        $useDataImport = $this->app['eccube.plugin.dataimport.repository.dataimport']->getLatestPreUseDataImport($Order);
        $useDataImport = abs($useDataImport);

        // 加算ポイントの取得
        $calculator = $this->app['eccube.plugin.dataimport.calculate.helper.factory'];
        $calculator->setUseDataImport($useDataImport); // calculatorに渡す際は絶対値
        $calculator->addEntity('Order', $Order);
        $calculator->addEntity('Customer', $Customer);
        $addDataImport = $calculator->getAddDataImportByOrder();

        // 受注明細がない場合にnullが返る. 通常では発生し得ないため. その場合は表示を行わない
        if (is_null($addDataImport)) {
            return true;
        }

        // 現在の保有ポイント取得
        $currentDataImport = $calculator->getDataImport();

        // 会員のポイントテーブルにレコードがない場合はnullを返す. その場合は0で表示する
        if (is_null($currentDataImport)) {
            $currentDataImport = 0;
        }

        // ポイント基本情報を取得
        $DataImportInfo = $this->app['eccube.plugin.dataimport.repository.dataimportinfo']->getLastInsertData();

        // ポイント表示用変数作成
        $dataimport = array();
        $dataimport['current'] = $currentDataImport;
        $dataimport['use'] = $useDataImport;
        $dataimport['add'] = $addDataImport;
        $dataimport['rate'] = $DataImportInfo->getPlgDataImportConversionRate();

        // 加算ポイント/利用ポイントを表示する
        $snippet = $this->app->renderView(
            'DataImport/Resource/template/default/Event/ShoppingConfirm/dataimport_summary.twig',
            array(
                'dataimport' => $dataimport,
            )
        );
        $search = '<p id="summary_box__total_amount"';
        $this->replaceView($event, $snippet, $search);

        // ポイント利用画面へのボタンを表示する
        $snippet = $this->app->renderView(
            'DataImport/Resource/template/default/Event/ShoppingConfirm/use_dataimport_button.twig',
            array(
                'dataimport' => $dataimport,
            )
        );
        $search = '<h2 class="heading02">お問い合わせ欄</h2>';
        $this->replaceView($event, $snippet, $search);
    }
}
