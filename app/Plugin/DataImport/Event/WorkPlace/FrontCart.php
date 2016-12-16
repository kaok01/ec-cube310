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
 *  - 拡張元 : カート
 *  - 拡張項目 : 画面表示
 * Class FrontCart
 * @package Plugin\DataImport\Event\WorkPlace
 */
class FrontCart extends AbstractWorkPlace
{
    /**
     * カートページにポイント情報を表示
     *
     * @param TemplateEvent $event
     * @return bool
     */
    public function createTwig(TemplateEvent $event)
    {
        // ポイント情報基本設定を取得
        $DataImportInfo = $this->app['eccube.plugin.dataimport.repository.dataimportinfo']->getLastInsertData();
        $dataimportRate = $DataImportInfo->getPlgDataImportConversionRate();

        $dataimport = array();
        $dataimport['rate'] = $dataimportRate;

        if ($this->app->isGranted('ROLE_USER')) {
            $calculator = $this->app['eccube.plugin.dataimport.calculate.helper.factory'];
            $Customer = $this->app->user();
            $parameters = $event->getParameters();
            $calculator->addEntity('Customer', $Customer);
            $calculator->addEntity('Cart', $parameters['Cart']);

            // 現在の保有ポイント
            $currentDataImport = $calculator->getDataImport();
            // カートの加算ポイント
            $addDataImport = $calculator->getAddDataImportByCart();
            // getDataImportはnullを返す場合がある.
            $dataimport['current'] = is_null($currentDataImport) ? 0 : $currentDataImport;
            $dataimport['add'] = $addDataImport;

            $template = 'DataImport/Resource/template/default/Event/Cart/dataimport_box.twig';
        } else {
            $template = 'DataImport/Resource/template/default/Event/Cart/dataimport_box_no_customer.twig';
        }

        $snippet = $this->app->renderView($template, array('dataimport' => $dataimport));

        $search = '<div id="cart_item_list"';
        $this->replaceView($event, $snippet, $search);
    }
}
