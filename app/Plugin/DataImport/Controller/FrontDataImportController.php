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
namespace Plugin\DataImport\Controller;

use Eccube\Application;
use Plugin\DataImport\Form\Type;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception as HttpException;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * データインポート設定画面用コントローラー
 * Class FrontDataImportController
 *
 * @package Plugin\DataImport\Controller
 */
class FrontDataImportController
{
    /**
     * 利用データインポート入力画面
     *
     * @param Application $app
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function useDataImport(Application $app, Request $request)
    {
        // ログイン済の会員のみデータインポートを利用できる
        if (!$app->isGranted('ROLE_USER')) {
            throw new HttpException\NotFoundHttpException;
        }

        $app['monolog.dataimport']->addInfo('useDataImport start');

        // カートが存在しない、カートがロックされていない時はエラー
        if (!$app['eccube.service.cart']->isLocked()) {
            return $app->redirect($app->url('cart'));
        }

        // 購入処理中の受注情報がない場合はエラー表示
        $Order = $app['eccube.service.shopping']->getOrder($app['config']['order_processing']);
        if (!$Order) {
            $app->addError('front.shopping.order.error');

            return $app->redirect($app->url('shopping_error'));
        }

        // データインポート換算レートの取得.
        $DataImportInfo = $app['eccube.plugin.dataimport.repository.dataimportinfo']->getLastInsertData();
        $dataimportRate = $DataImportInfo->getPlgDataImportConversionRate();

        // 保有データインポートの取得.
        $Customer = $app->user();
        $currentDataImport = $app['eccube.plugin.dataimport.repository.dataimportcustomer']->getLastDataImportById($Customer->getId());

        // 利用中のデータインポートの取得.
        $lastPreUseDataImport = $app['eccube.plugin.dataimport.repository.dataimport']->getLatestPreUseDataImport($Order);
        $lastPreUseDataImport = abs($lastPreUseDataImport); // 画面上では正の値として表示する.

        // すべての値引きを除外した合計金額
        $totalPrice = $Order->getTotalPrice() + $Order->getDiscount();

        // データインポートによる値引きを除外した合計金額
        $totalPriceExcludeDataImport = $Order->getTotalPrice() + $lastPreUseDataImport * $dataimportRate;

        // データインポートによる値引きを除外した合計金額を、換算レートで割戻し、利用できるデータインポートの上限値を取得する.
        $maxUseDataImport = floor($totalPriceExcludeDataImport / $dataimportRate);

        $form = $app['form.factory']
            ->createBuilder('front_dataimport_use',
                array(
                    'plg_use_dataimport' => $lastPreUseDataImport
                ),
                array(
                    'maxUseDataImport' => $maxUseDataImport,
                    'currentDataImport' => $currentDataImport
                )
            )
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $useDataImport = $form['plg_use_dataimport']->getData();

            $app['monolog.dataimport']->addInfo(
                'useDataImport data',
                array(
                    'customer_id' => $Order->getCustomer()->getId(),
                    'use dataimport' => $useDataImport,
                )
            );

            // 利用中のデータインポートと入力されたデータインポートに相違があれば保存.
            if ($lastPreUseDataImport != $useDataImport) {

                $calculator = $app['eccube.plugin.dataimport.calculate.helper.factory'];
                $calculator->addEntity('Order', $Order);
                $calculator->addEntity('Customer', $Order->getCustomer());
                $calculator->setUseDataImport($useDataImport);

                // 受注情報に対し, 値引き金額の設定を行う
                if ($calculator->setDiscount($lastPreUseDataImport)) {
                    // 受注情報に対し、合計金額を再計算し、設定する.
                    $newOrder = $app['eccube.service.shopping']->calculatePrice($Order);

                    // ユーザー入力値を保存
                    $app['eccube.plugin.dataimport.history.service']->refreshEntity();
                    $app['eccube.plugin.dataimport.history.service']->addEntity($Order);
                    $app['eccube.plugin.dataimport.history.service']->addEntity($Order->getCustomer());
                    $app['eccube.plugin.dataimport.history.service']->savePreUseDataImport($useDataImport * -1); // 登録時に負の値に変換

                    $app['orm.em']->persist($newOrder);
                    $app['orm.em']->flush($newOrder);
                }
            }

            $app['monolog.dataimport']->addInfo('useDataImport end');

            return $app->redirect($app->url('shopping'));
        }

        $app['monolog.dataimport']->addInfo('useDataImport end');

        return $app->render(
            'DataImport/Resource/template/default/dataimport_use.twig',
            array(
                'form' => $form->createView(),  // フォーム
                'dataimportRate' => $dataimportRate,      // 換算レート
                'currentDataImport' => $currentDataImport,  // 保有データインポート
                // 利用データインポート上限. 保有データインポートが小さい場合は保有データインポートを上限値として表示する
                'maxUseDataImport' => ($maxUseDataImport < $currentDataImport) ? $maxUseDataImport : $currentDataImport,
                'total' => $totalPrice, // すべての値引きを除外した合計金額
            )
        );
    }
}
