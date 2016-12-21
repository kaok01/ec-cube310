<?php
/*
* This file is part of EC-CUBE
*
* Copyright(c) 2000-2015 LOCKON CO.,LTD. All Rights Reserved.
* http://www.lockon.co.jp/
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace Plugin\CustomerTag;

use Eccube\Common\Constant;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;

class CustomerTag
{
    private $app;

    public function __construct($app)
    {
        $this->app = $app;
    }

    public function onRenderAdminCustomerNewBefore(FilterResponseEvent $event)
    {
        $app = $this->app;
        if (!$this->app->isGranted('ROLE_ADMIN')) {
            return;
        }

        $request = $event->getRequest();
        $response = $event->getResponse();
        $id = $request->attributes->get('id');

        list($html, $form) = $this->getHtml($request, $response, $id);
        $response->setContent($html);

        if ('POST' === $request->getMethod()) {
            // RedirectResponseかどうかで判定する.

            if (!$response instanceof RedirectResponse) {
                return;
            }
            if (empty($id)) {
                $location = explode('/', $response->headers->get('location'));
                $url = explode('/', $this->app->url('admin_customer_edit', array('id' => '0')));
                $diffs = array_values(array_diff($location, $url));
                $id = $diffs[0];
            }

            if ($form->isValid()) {
                // 登録
                $data = $form->getData();

                $CustomerTags = $this->app['eccube.plugin.customertag.repository.customertag']->findAll();

                $CustomerTag = $form->get('customertag')->getData();
                $customertagUrl = $form->get('customertag_url')->getData();

                if (count($CustomerTags) > 0 && !empty($CustomerTag)) {

                    $CustomerCustomerTag = new \Plugin\CustomerTag\Entity\CustomerCustomerTag();

                    $CustomerCustomerTag
                        ->setId($id)
                        ->setCustomerTagUrl($customertagUrl)
                        ->setDelFlg(Constant::DISABLED)
                        ->setCustomerTag($CustomerTag);

                    $app['orm.em']->persist($CustomerCustomerTag);

                    $app['orm.em']->flush($CustomerCustomerTag);
                }
            }
        }

        $event->setResponse($response);
    }

    public function onRenderAdminCustomerEditBefore(FilterResponseEvent $event)
    {
        $app = $this->app;
        if (!$app->isGranted('ROLE_ADMIN')) {
            return;
        }

        $request = $event->getRequest();
        $response = $event->getResponse();
        $id = $request->attributes->get('id');

        list($html, $form) = $this->getHtml($request, $response, $id);
        $response->setContent($html);

        $event->setResponse($response);
    }


    public function onAdminCustomerEditAfter()
    {
        $app = $this->app;
        if (!$app->isGranted('ROLE_ADMIN')) {
            return;
        }

        $id = $app['request']->attributes->get('id');

        $form = $app['form.factory']
            ->createBuilder('admin_customer')
            ->getForm();

        $CustomerCustomerTag = $app['eccube.plugin.customertag.repository.product_customertag']->find($id);

        if (is_null($CustomerCustomerTag)) {
            $CustomerCustomerTag = new \Plugin\CustomerTag\Entity\CustomerCustomerTag();
        }

        $form->get('customertag')->setData($CustomerCustomerTag->getCustomerTag());

        $form->handleRequest($app['request']);

        if ('POST' === $app['request']->getMethod()) {

            if ($form->get('customertag')->isValid()) {

                $customertag_id = $form->get('customertag')->getData();
                if ($customertag_id) {
                // 登録・更新
                    $CustomerTag = $app['eccube.plugin.customertag.repository.customertag']->find($customertag_id);
                // ※setIdはなんだか違う気がする
                    if ($id) {
                        $CustomerCustomerTag->setId($id);
                    }

                    $CustomerCustomerTag
                        ->setCustomerTagUrl($form->get('customertag_url')->getData())
                        ->setDelFlg(0)
                        ->setCustomerTag($CustomerTag);
                        $app['orm.em']->persist($CustomerCustomerTag);
                } else {
                // 削除
                // ※setIdはなんだか違う気がする
                    $CustomerCustomerTag->setId($id);
                    $app['orm.em']->remove($CustomerCustomerTag);
                }

                $app['orm.em']->flush();
            }
        }
    }

    private function getHtml($request, $response, $id)
    {

        // メーカーマスタから有効なメーカー情報を取得
        $CustomerTags = $this->app['eccube.plugin.customertag.repository.customertag']->findAll();

        if (is_null($CustomerTags)) {
            $CustomerTags = new \Plugin\CustomerTag\Entity\CustomerTag();
        }

        $CustomerCustomerTag = null;

        if ($id) {
            // 商品メーカーマスタから設定されているなメーカー情報を取得
            $CustomerCustomerTag = $this->app['eccube.plugin.customertag.repository.customer_customertag']->find($id);
        }

        // 商品登録・編集画面のHTMLを取得し、DOM化
        $crawler = new Crawler($response->getContent());

        $form = $this->app['form.factory']
            ->createBuilder('admin_customer')
            ->getForm();

        if ($CustomerCustomerTag) {
            // 既に登録されている商品メーカー情報が設定されている場合、初期選択
            $form->get('customertag')->setData($CustomerCustomerTag->getCustomerTag());
            $form->get('customertag_url')->setData($CustomerCustomerTag->getCustomerTagUrl());
        }

        $form->handleRequest($request);

        $parts = $this->app->renderView(
            'CustomerTag/View/admin/customer_customertag.twig',
            array('form' => $form->createView())
        );

        // form1の最終項目に追加(レイアウトに依存
        $html = $this->getHtmlFromCrawler($crawler);

        try {
            $oldHtml = $crawler->filter('#form1 .accordion')->last()->html();//dump($oldHtml);
        $oldHtml2 = html_entity_decode($oldHtml, ENT_NOQUOTES, 'UTF-8');//dump($oldHtml2);
            $newHtml = $oldHtml2.$parts;//dump($newHtml);
            $html = str_replace($oldHtml2, $newHtml, $html);//dump($html);
        } catch (\InvalidArgumentException $e) {
            // no-op
        }

        return array($html, $form);

    }


    public function onRenderCustomersDetailBefore(FilterResponseEvent $event)
    {
        $app = $this->app;
        $request = $event->getRequest();
        $response = $event->getResponse();
        $id = $request->attributes->get('id');

        $CustomerCustomerTag = null;

        if ($id) {
            // 商品メーカーマスタから設定されているなメーカー情報を取得
            $CustomerCustomerTag = $this->app['eccube.plugin.customertag.repository.customer_customertag']->find($id);
        }
        if (!$CustomerCustomerTag) {
            return;
        }

        $CustomerTag = $CustomerCustomerTag->getCustomerTag();

        if (is_null($CustomerTag)) {
            // 商品メーカーマスタにデータが存在しないまたは削除されていれば無視する
            return;
        }

        // HTMLを取得し、DOM化
        $crawler = new Crawler($response->getContent());
        $html = $this->getHtmlFromCrawler($crawler);

        if ($CustomerCustomerTag) {
            $parts = $this->app->renderView(
                'CustomerTag/View/default/customertag.twig',
                array(
                    'customertag_name' => $CustomerCustomerTag->getCustomerTag()->getName(),
                    'customertag_url' => $CustomerCustomerTag->getCustomerTagUrl(),
                )
            );

            try {
                // ※商品コードの下に追加
                $parts_item_code = $crawler->filter('.item_code')->html();
                $new_html = $parts_item_code.$parts;
                $html = str_replace($parts_item_code, $new_html, $html);
            } catch (\InvalidArgumentException $e) {
                // no-op
            }
        }

        $response->setContent($html);
        $event->setResponse($response);
    }

    /**
     * 解析用HTMLを取得.
     *
     * @param Crawler $crawler
     *
     * @return string
     */
    private function getHtmlFromCrawler(Crawler $crawler)
    {
        $html = '';
        foreach ($crawler as $domElement) {
            $domElement->ownerDocument->formatOutput = true;
            $html .= $domElement->ownerDocument->saveHTML();
        }

        return html_entity_decode($html, ENT_NOQUOTES, 'UTF-8');
    }
}
