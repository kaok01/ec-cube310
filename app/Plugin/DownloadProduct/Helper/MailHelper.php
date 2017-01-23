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
namespace Plugin\DownloadProduct\Helper;

use Eccube\Application;
use Eccube\Entity\Order;

class MailHelper
{

    /** @var \Eccube\Application */
    public $app;


    /** @var \Eccube\Entity\BaseInfo */
    public $BaseInfo;

    public function __construct(Application $app)
    {
        $this->app = $app;
        $this->BaseInfo = $app['eccube.repository.base_info']->get();
    }


    /**
     * ダウンロード商品でマイナス発生時にメール通知する。
     *
     * @param Order $Order
     * @param int $currentDownloadProduct
     * @param int $useDownloadProduct
     */
    public function sendDownloadProductNotifyMail(Order $Order, $currentDownloadProduct = 0, $useDownloadProduct = 0)
    {

        $body = $this->app->renderView('DownloadProduct/Resource/template/admin/Mail/downloadproduct_notify.twig', array(
            'Order' => $Order,
            'currentDownloadProduct' => $currentDownloadProduct,
            'useDownloadProduct' => abs($useDownloadProduct), // DBから取得した利用ダウンロード商品はマイナス値なので、絶対値で表示する
        ));

        $message = \Swift_Message::newInstance()
            ->setSubject('['.$this->BaseInfo->getShopName().'] ダウンロード商品通知')
            ->setFrom(array($this->BaseInfo->getEmail01() => $this->BaseInfo->getShopName()))
            ->setTo(array($this->BaseInfo->getEmail01()))
            ->setBcc($this->BaseInfo->getEmail01())
            ->setReplyTo($this->BaseInfo->getEmail03())
            ->setReturnPath($this->BaseInfo->getEmail04())
            ->setBody($body);

        $this->app->mail($message);
    }

}