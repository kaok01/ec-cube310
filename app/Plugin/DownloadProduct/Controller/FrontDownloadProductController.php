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
namespace Plugin\DownloadProduct\Controller;

use Eccube\Application;
use Plugin\DownloadProduct\Form\Type;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception as HttpException;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * ダウンロード商品設定画面用コントローラー
 * Class FrontDownloadProductController
 *
 * @package Plugin\DownloadProduct\Controller
 */
class FrontDownloadProductController
{
    /**
     * 利用ダウンロード商品入力画面
     *
     * @param Application $app
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function useDownloadProduct(Application $app, Request $request)
    {
        // ログイン済の会員のみダウンロード商品を利用できる
        if (!$app->isGranted('ROLE_USER')) {
            throw new HttpException\NotFoundHttpException;
        }

        $app['monolog.downloadproduct']->addInfo('useDownloadProduct start');

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

        // ダウンロード商品換算レートの取得.
        $DownloadProductInfo = $app['eccube.plugin.downloadproduct.repository.downloadproductinfo']->getLastInsertData();
        $downloadproductRate = $DownloadProductInfo->getPlgDownloadProductConversionRate();

        // 保有ダウンロード商品の取得.
        $Customer = $app->user();
        $currentDownloadProduct = $app['eccube.plugin.downloadproduct.repository.downloadproductcustomer']->getLastDownloadProductById($Customer->getId());

        // 利用中のダウンロード商品の取得.
        $lastPreUseDownloadProduct = $app['eccube.plugin.downloadproduct.repository.downloadproduct']->getLatestPreUseDownloadProduct($Order);
        $lastPreUseDownloadProduct = abs($lastPreUseDownloadProduct); // 画面上では正の値として表示する.

        // すべての値引きを除外した合計金額
        $totalPrice = $Order->getTotalPrice() + $Order->getDiscount();

        // ダウンロード商品による値引きを除外した合計金額
        $totalPriceExcludeDownloadProduct = $Order->getTotalPrice() + $lastPreUseDownloadProduct * $downloadproductRate;

        // ダウンロード商品による値引きを除外した合計金額を、換算レートで割戻し、利用できるダウンロード商品の上限値を取得する.
        $maxUseDownloadProduct = floor($totalPriceExcludeDownloadProduct / $downloadproductRate);

        $form = $app['form.factory']
            ->createBuilder('front_downloadproduct_use',
                array(
                    'plg_use_downloadproduct' => $lastPreUseDownloadProduct
                ),
                array(
                    'maxUseDownloadProduct' => $maxUseDownloadProduct,
                    'currentDownloadProduct' => $currentDownloadProduct
                )
            )
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $useDownloadProduct = $form['plg_use_downloadproduct']->getData();

            $app['monolog.downloadproduct']->addInfo(
                'useDownloadProduct data',
                array(
                    'customer_id' => $Order->getCustomer()->getId(),
                    'use downloadproduct' => $useDownloadProduct,
                )
            );

            // 利用中のダウンロード商品と入力されたダウンロード商品に相違があれば保存.
            if ($lastPreUseDownloadProduct != $useDownloadProduct) {

                $calculator = $app['eccube.plugin.downloadproduct.calculate.helper.factory'];
                $calculator->addEntity('Order', $Order);
                $calculator->addEntity('Customer', $Order->getCustomer());
                $calculator->setUseDownloadProduct($useDownloadProduct);

                // 受注情報に対し, 値引き金額の設定を行う
                if ($calculator->setDiscount($lastPreUseDownloadProduct)) {
                    // 受注情報に対し、合計金額を再計算し、設定する.
                    $newOrder = $app['eccube.service.shopping']->calculatePrice($Order);

                    // ユーザー入力値を保存
                    $app['eccube.plugin.downloadproduct.history.service']->refreshEntity();
                    $app['eccube.plugin.downloadproduct.history.service']->addEntity($Order);
                    $app['eccube.plugin.downloadproduct.history.service']->addEntity($Order->getCustomer());
                    $app['eccube.plugin.downloadproduct.history.service']->savePreUseDownloadProduct($useDownloadProduct * -1); // 登録時に負の値に変換

                    $app['orm.em']->persist($newOrder);
                    $app['orm.em']->flush($newOrder);
                }
            }

            $app['monolog.downloadproduct']->addInfo('useDownloadProduct end');

            return $app->redirect($app->url('shopping'));
        }

        $app['monolog.downloadproduct']->addInfo('useDownloadProduct end');

        return $app->render(
            'DownloadProduct/Resource/template/default/downloadproduct_use.twig',
            array(
                'form' => $form->createView(),  // フォーム
                'downloadproductRate' => $downloadproductRate,      // 換算レート
                'currentDownloadProduct' => $currentDownloadProduct,  // 保有ダウンロード商品
                // 利用ダウンロード商品上限. 保有ダウンロード商品が小さい場合は保有ダウンロード商品を上限値として表示する
                'maxUseDownloadProduct' => ($maxUseDownloadProduct < $currentDownloadProduct) ? $maxUseDownloadProduct : $currentDownloadProduct,
                'total' => $totalPrice, // すべての値引きを除外した合計金額
            )
        );
    }
}
