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

use Eccube\Event\EventArgs;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * フックデータインポート汎用処理具象クラス
 *  - 拡張元 : 受注メール
 *  - 拡張項目 : メール内容
 * Class ServiceMail
 *
 * @package Plugin\DataImport\Event\WorkPlace
 */
class ServiceMail extends AbstractWorkPlace
{

    /**
     * メール本文の置き換え
     *
     * @param EventArgs $event
     * @return bool
     */
    public function save(EventArgs $event)
    {

        $this->app['monolog.dataimport']->addInfo('save start');

        // 基本情報の取得
        $message = $event->getArgument('message');
        $order = $event->getArgument('Order');

        // 必要情報判定
        if (empty($message) || empty($order)) {
            return false;
        }

        $customer = $order->getCustomer();
        if (empty($customer)) {
            return false;
        }


        // 計算ヘルパーの取得
        $calculator = $this->app['eccube.plugin.dataimport.calculate.helper.factory'];

        // 利用データインポートの取得と設定
        $useDataImport = $this->app['eccube.plugin.dataimport.repository.dataimport']->getLatestUseDataImport($order);
        $useDataImport = abs($useDataImport);

        $calculator->setUseDataImport($useDataImport);
        // 計算に必要なエンティティの設定
        $calculator->addEntity('Order', $order);
        $calculator->addEntity('Customer', $customer);

        // 計算値取得
        $addDataImport = $this->app['eccube.plugin.dataimport.repository.dataimport']->getLatestAddDataImportByOrder($order);

        $this->app['monolog.dataimport']->addInfo('save add dataimport', array(
                'customer_id' => $customer->getId(),
                'order_id' => $order->getId(),
                'add dataimport' => $addDataImport,
                'use dataimport' => $useDataImport,
            )
        );

        // メールボディ取得
        $body = $message->getBody();

        // 情報置換用のキーを取得
        $search = array();
        preg_match_all('/合　計.*\\n/u', $body, $search);

        // メール本文置換
        $snippet = PHP_EOL;
        $snippet .= PHP_EOL;
        $snippet .= '***********************************************'.PHP_EOL;
        $snippet .= '　データインポート情報                                 '.PHP_EOL;
        $snippet .= '***********************************************'.PHP_EOL;
        $snippet .= PHP_EOL;
        $snippet .= '利用データインポート：'.number_format($useDataImport).' pt'.PHP_EOL;
        $snippet .= '加算データインポート：'.number_format($addDataImport).' pt'.PHP_EOL;
        $snippet .= PHP_EOL;
        $replace = $search[0][0].$snippet;
        $body = preg_replace('/'.$search[0][0].'/u', $replace, $body);

        // メッセージにメールボディをセット
        $message->setBody($body);

        $this->app['monolog.dataimport']->addInfo('save end');

    }
}
