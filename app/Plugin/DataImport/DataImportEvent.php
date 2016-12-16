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
namespace Plugin\DataImport;

use Eccube\Application;
use Eccube\Event\EventArgs;
use Eccube\Event\TemplateEvent;
use Eccube\Exception\ShoppingException;
use Plugin\DataImport\Event\WorkPlace\AdminCustomer;
use Plugin\DataImport\Event\WorkPlace\AdminOrder;
use Plugin\DataImport\Event\WorkPlace\AdminOrderMail;
use Plugin\DataImport\Event\WorkPlace\AdminProduct;
use Plugin\DataImport\Event\WorkPlace\FrontCart;
use Plugin\DataImport\Event\WorkPlace\FrontChangeTotal;
use Plugin\DataImport\Event\WorkPlace\FrontDelivery;
use Plugin\DataImport\Event\WorkPlace\FrontHistory;
use Plugin\DataImport\Event\WorkPlace\FrontMyPage;
use Plugin\DataImport\Event\WorkPlace\FrontPayment;
use Plugin\DataImport\Event\WorkPlace\FrontProductDetail;
use Plugin\DataImport\Event\WorkPlace\FrontShipping;
use Plugin\DataImport\Event\WorkPlace\FrontShopping;
use Plugin\DataImport\Event\WorkPlace\FrontShoppingComplete;
use Plugin\DataImport\Event\WorkPlace\ServiceMail;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;


/**
 * ポイントプラグインイベント処理ルーティングクラス
 * Class DataImportEvent
 * @package Plugin\DataImport
 */
class DataImportEvent
{

    /** @var  \Eccube\Application $app */
    protected $app;

    /**
     * DataImportEvent constructor.
     * @param $app
     */
    public function __construct($app)
    {
        $this->app = $app;
    }

    /**
     * 商品毎ポイント付与率
     *  - フォーム拡張処理
     *  - 管理画面 > 商品編集
     * @param EventArgs $event
     */
    public function onAdminProductEditInitialize(EventArgs $event)
    {
        return;
        
        $helper = new AdminProduct();
        $helper->createForm($event, $this->app['request']);
    }

    /**
     * 商品毎ポイント付与率
     *  - 保存処理
     *  - 管理画面 > 商品編集
     * @param EventArgs $event
     */
    public function onAdminProductEditComplete(EventArgs $event)
    {
        return;
        $helper = new AdminProduct();
        $helper->save($event);
    }

    /**
     * 会員保有ポイント
     *  - フォーム拡張処理
     *  - 管理画面 > 会員編集
     * @param EventArgs $event
     */
    public function onAdminCustomerEditIndexInitialize(EventArgs $event)
    {
        return;
        $helper = new AdminCustomer();
        $helper->createForm($event, $this->app['request']);
    }

    /**
     * 会員保有ポイント
     *  - 保存処理
     *  - 管理画面 > 会員編集
     * @param EventArgs $event
     */
    public function onAdminCustomerEditIndexComplete(EventArgs $event)
    {
        return;
        $helper = new AdminCustomer();
        $helper->save($event);
    }

    /**
     * 受注ステータス登録・編集
     *  - フォーム項目追加
     *  - 管理画面 > 受注登録 ( 編集 )
     * @param EventArgs $event
     */
    public function onAdminOrderEditIndexInitialize(EventArgs $event)
    {
        return;
        $helper = new AdminOrder();
        $helper->createForm($event);
        $helper->checkAbuseOrder($event->getArgument('OriginOrder'));
    }

    /**
     * 受注ステータス変更時ポイント付与
     *  - 判定・更新処理
     *  - 管理画面 > 受注登録 ( 編集 )
     * @param EventArgs $event
     */
    public function onAdminOrderEditIndexComplete(EventArgs $event)
    {
        return;
        $helper = new AdminOrder();
        $helper->save($event);
    }

    /**
     * 受注削除
     * @param EventArgs $event
     */
    public function onAdminOrderDeleteComplete(EventArgs $event)
    {
        return;
        $helper = new AdminOrder();
        $helper->delete($event);
    }

    /**
     * メール通知
     * @param EventArgs $event
     */
    public function onAdminOrderMailIndexComplete(EventArgs $event)
    {
        return;
        $helper = new AdminOrderMail();
        $helper->save($event);
    }

    /**
     * 商品購入確認完了
     *  - 利用ポイント・保有ポイント・仮付与ポイント保存
     *  - フロント画面 > 商品購入確認完了
     * @param EventArgs $event
     */
    public function onFrontShoppingConfirmProcessing(EventArgs $event)
    {
        return;
        // ログイン判定
        if ($this->isAuthRouteFront()) {
            $helper = new FrontShoppingComplete();
            $helper->save($event);
        }
    }

    /**
     * 商品購入確認完了
     *  - 利用ポイント・保有ポイント・仮付与ポイントメール反映
     *  - フロント画面 > 商品購入完了
     * @param EventArgs $event
     */
    public function onServiceShoppingNotifyComplete(EventArgs $event)
    {
        return;
        // ログイン判定
        if ($this->isAuthRouteFront()) {
            $helper = new FrontShoppingComplete();
            $helper->save($event);
        }
    }

    /**
     * 商品購入完了画面
     * @param TemplateEvent $event
     */
    public function onRenderShoppingComplete(TemplateEvent $event)
    {
        return;
        // ログイン判定
        if ($this->isAuthRouteFront()) {
            $helper = new FrontShoppingComplete();
            $helper->createTwig($event);
        }
    }

    /**
     * 合計金額の変更時のハンドリングを行う
     *  - 配送業者変更時の合計金額判定処理
     *  - お届け先変更時の合計金額判定処理
     *  - 支払い方法変更時の合計金額判定処理
     *
     * @param TemplateEvent $event
     */
    public function onFrontChangeTotal(EventArgs $event)
    {
        return;
        // ログイン判定
        if ($this->isAuthRouteFront()) {
            $helper = new FrontChangeTotal();
            $helper->save($event);
        }
    }

    /**
     * 商品購入確認画面
     *  - ポイント使用処理
     *  - 付与ポイント計算処理・画面描画処理
     *  - フロント画面 > 商品購入確認画面
     * @param TemplateEvent $event
     */
    public function onRenderShoppingIndex(TemplateEvent $event)
    {
        return;
        // ログイン判定
        if ($this->isAuthRouteFront()) {
            $helper = new FrontShopping();
            $helper->createTwig($event);
        }
    }

    /**
     * 管理画面受注編集
     *  - 利用ポイント・保有ポイント・付与ポイント表示
     *  - 管理画面 > 受注情報登録・編集
     * @param TemplateEvent $event
     */
    public function onRenderAdminOrderEdit(TemplateEvent $event)
    {
        return;
        $helper = new AdminOrder();
        $helper->createTwig($event);
    }

    /**
     *  マイページ
     *  - 利用ポイント・保有ポイント表示
     *
     * @param TemplateEvent $event
     */
    public function onRenderMyPageIndex(TemplateEvent $event)
    {
        return;
        // ログイン判定
        if ($this->isAuthRouteFront()) {
            $helper = new FrontMyPage();
            $helper->createTwig($event);
        }
    }

    /**
     * メール通知
     * @param TemplateEvent $event
     */
    public function onRenderAdminOrderMailConfirm(TemplateEvent $event)
    {
        return;
        $helper = new AdminOrderMail();
        $helper->createTwig($event);
    }

    /**
     * 商品購入完了メール
     *  - ポイントの表示
     *
     * @param EventArgs $event
     */
    public function onMailOrderComplete(EventArgs $event)
    {
        return;
        // ログイン判定
        if ($this->isAuthRouteFront() || $this->app->isGranted('ROLE_ADMIN')) {
            $helper = new ServiceMail();
            $helper->save($event);
        }
    }

    /**
     * 商品詳細画面
     *  - 加算ポイント表示
     *
     * @param TemplateEvent $event
     */
    public function onRenderProductDetail(TemplateEvent $event)
    {
        return;
        $helper = new FrontProductDetail();
        $helper->createTwig($event);
    }

    /**
     * カート画面
     *  - 利用ポイント・保有ポイント・加算ポイント表示
     *
     * @param TemplateEvent $event
     */
    public function onRenderCart(TemplateEvent $event)
    {
        return;
        $helper = new FrontCart();
        $helper->createTwig($event);
    }

    /**
     * マイページ履歴画面
     *  - 利用ポイント・保有ポイント表示
     *
     * @param TemplateEvent $event
     */
    public function onRenderHistory(TemplateEvent $event)
    {
        return;
        // ログイン判定
        if ($this->isAuthRouteFront()) {
            $helper = new FrontHistory();
            $helper->createTwig($event);
        }
    }

    /**
     * フロント画面権限確認
     *
     * @return bool
     */
    protected function isAuthRouteFront()
    {
        return $this->app->isGranted('ROLE_USER');
    }
}
