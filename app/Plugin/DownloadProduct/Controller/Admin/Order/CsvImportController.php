<?php
/*
 * This file is part of EC-CUBE
 *
 * Copyright(c) 2000-2015 LOCKON CO.,LTD. All Rights Reserved.
 *
 * http://www.lockon.co.jp/
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 */


namespace Plugin\DownloadProduct\Controller\Admin\Order;

use Eccube\Application;
use Eccube\Common\Constant;
use Eccube\Entity\Order;
//use Plugin\DownloadProduct\Entity\CustomerTag;
use Eccube\Exception\CsvImportException;
use Eccube\Service\CsvImportService;
use Eccube\Util\Str;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

use Doctrine\Common\Collections\ArrayCollection;
use Eccube\Entity\ShipmentItem;
//use Eccube\Event\EccubeEvents;
//use Eccube\Event\EventArgs;

class CsvImportController extends \Plugin\DownloadProduct\Controller\Base\CsvImportController
{


    private $orderTwig = 'DownloadProduct/Resource/template/admin/Order/csv_order.twig';
    private $app;


    /**
     * 会員登録CSVアップロード
     */
    public function csvOrder(Application $app, Request $request)
    {
        $this->app = $app;
//dump($request);die();
        $form = $app['form.factory']->createBuilder('admin_csv_import')->getForm();

        $headers = $this->getOrderCsvHeader();

        if ('POST' === $request->getMethod()) {

            $form->handleRequest($request);

            if ($form->isValid()) {


                $formFile = $form['import_file']->getData();

                if (!empty($formFile)) {

                    //log_info('会員CSV登録開始');

                    $data = $this->getImportData($app, $formFile);

                    if ($data === false) {
                        $this->addErrors('CSVのフォーマットが一致しません。');
                        return $this->render($app, $form, $headers, $this->orderTwig);
                    }

                    $keys = array_keys($headers);

                    $columnHeaders = $data->getColumnHeaders();
                    if ($keys !== $columnHeaders) {
                        $this->addErrors('CSVのフォーマットが一致しません。');
                        return $this->render($app, $form, $headers, $this->orderTwig);
                    }

                    $size = count($data);
                    if ($size < 1) {
                        $this->addErrors('CSVデータが存在しません。');
                        return $this->render($app, $form, $headers, $this->orderTwig);
                    }

                    $headerSize = count($keys);

                    $this->em = $app['orm.em'];
                    $this->em->getConfiguration()->setSQLLogger(null);

                    $this->em->getConnection()->beginTransaction();

                    $BaseInfo = $app['eccube.repository.base_info']->get();

                    // CSVファイルの登録処理
                    foreach ($data as $row) {

                        if ($headerSize != count($row)) {
                            $this->addErrors(($data->key() + 1) . "行目のCSVフォーマットが一致しません。");
                            return $this->render($app, $form, $headers, $this->orderTwig);
                        }

                        $id = $row['注文ID'];
                        $del_flg = $row['削除フラグ'];

                        $TargetOrder = null;
                        $OriginOrder = null;
                        $noAction = false;
                        $Overwrite = false;

                        if (empty($id)) {
                            // 空のエンティティを作成.
                            
                            $refid = $row['連携注文ID'];
                            if($refid){

                                $DownloadProductOrder = $app['eccube.plugin.downloadproduct.repository.downloadproductorder']->find($refid);
                                if($DownloadProductOrder){

                                    $noAction = true;
                                    $Overwrite = true;

                                    $TargetOrder = $app['eccube.repository.order']->find($DownloadProductOrder->getOrder()->getId());
                                    if(is_null($TargetOrder)){
                                        $this->addErrors(($data->key() + 1) . '行目の連携注文IDが存在しません。');
                                        return $this->render($app, $form, $headers, $this->orderTwig);

                                    }
                                }else{

                                    $TargetOrder = $this->newOrder();

                                }

                            }else{
                                $TargetOrder = $this->newOrder();

                            }
                        }

                        if(is_null($TargetOrder)){
                            $TargetOrder = $app['eccube.repository.order']->find($id);
                            if (is_null($TargetOrder)) {
                                $this->addErrors(($data->key() + 1) . '行目の注文IDが存在しません。');
                                return $this->render($app, $form, $headers, $this->orderTwig);
                            }
                        }
                        /*

                                    '注文ID'=>'id',
                                    '連携注文ID'=>'refid',
                                    'メールアドレス'=>'email',
                                    '受注日'=>'order_date',
                                    '注文詳細ID'=>'order_detail_id',
                                    '商品ID'=>'product_id',
                                    '連携商品ID'=>'refproduct_id',
                                    '商品名'=>'product_name',
                                    '小計'=>'price',
                                    "販売個数"=>"order_num",
                                    "削除フラグ"=>"del_flg",
                        */
                        if(!$noAction && $del_flg!=1){

                            if($Overwrite){
                                
                                // foreach($TargetOrder->getShippings() as $currshp){
                                //     $app['orm.em']->remove($currshp);
                                //     $app['orm.em']->flush();

                                // }

                                foreach($TargetOrder->getOrderDetails() as $currod){
                                    $app['orm.em']->remove($currod);
                                    $app['orm.em']->flush();


                                }

                            }


                            $refproductid = $row['連携商品ID'];
                            if($refproductid){

                                $ProductMap = $app['eccube.plugin.downloadproduct.repository.productmap_product']
                                        ->findBy(array('refid'=>$refproductid));


                                if($ProductMap){
                                    $TargetProduct = $app['eccube.repository.product']->find($ProductMap[0]->getProduct()->getId());

                                }
                                if($TargetProduct){


                                }else{

                                    $this->addErrors(($data->key() + 1) . '行目の連携商品IDに対応する商品が登録されていません。');
                                    return $this->render($app, $form, $headers, $this->orderTwig);
                                }

                            }else{
                                $this->addErrors(($data->key() + 1) . '行目の連携商品IDが設定されていません。');
                                return $this->render($app, $form, $headers, $this->orderTwig);

                            }

                            $email = $row['メールアドレス'];
                            if($email){

                                $TargetCustomer = $app['eccube.repository.customer']->findBy(array('email'=>$email));
                                if($TargetCustomer){
                                    

                                }else{

                                    $this->addErrors(($data->key() + 1) . '行目のメールに対応する会員が登録されていません。');
                                    return $this->render($app, $form, $headers, $this->orderTwig);
                                }

                            }else{
                                $this->addErrors(($data->key() + 1) . '行目のメールアドレスが設定されていません。');
                                return $this->render($app, $form, $headers, $this->orderTwig);

                            }
                            $orderdt = $row['受注日'];
                            if($orderdt){

                                $Orderdt = new \Datetime($orderdt);
                                if($Orderdt){
                                    $TargetOrder->setOrderDate($Orderdt);

                                }else{

                                    $this->addErrors(($data->key() + 1) . '行目の受注日が設定されていません。');
                                    return $this->render($app, $form, $headers, $this->orderTwig);
                                }

                            }else{
                                $this->addErrors(($data->key() + 1) . '行目の受注日が設定されていません');
                                return $this->render($app, $form, $headers, $this->orderTwig);

                            }
                            $productclass = null;

                            foreach($TargetProduct->getProductClasses() as $pc){
                                $productclass = $pc;

                            }
                            $TaxRule = $app['eccube.repository.tax_rule']->getByRule();

                            $orderprice = $row['小計'];
                            if($orderprice){
                                $orderinctax = $orderprice * (100+$TaxRule->getTaxRate()) / 100;

                            }else{
                                //
                                $orderprice = $productclass->getPrice01()
                                            ?$productclass->getPrice02()
                                            :$productclass->getPrice01();
                                $orderinctax = $productclass->getPrice01IncTax()
                                            ?$productclass->getPrice02IncTax()
                                            :$productclass->getPrice01IncTax();



                            }

                            $ordernum = $row['販売個数'];
                            if($ordernum){

                            }else{
                                //
                                $ordernum = 1;

                            }




                            $detail = new \Eccube\Entity\OrderDetail();
                            $detail->setOrder($TargetOrder)
                                ->setProduct($TargetProduct)
                                ->setProductClass($productclass)
                                ->setQuantity($ordernum)
                                ->setTaxRate($TaxRule->getTaxRate())
                                ->setPriceIncTax($orderinctax)
                                ->setPrice($orderprice);

                            $TargetOrder->addOrderDetail($detail);
                            $TargetOrder->setCustomer($TargetCustomer[0]);

                            $TargetOrder->setTotal($TargetOrder->getTotalPrice());


                            $TargetOrder
                                ->setName01($TargetCustomer[0]->getName01())
                                ->setName02($TargetCustomer[0]->getName02())
                                ->setKana01($TargetCustomer[0]->getKana01())
                                ->setKana02($TargetCustomer[0]->getKana02())
                                ->setZip01($TargetCustomer[0]->getZip01())
                                ->setZip02($TargetCustomer[0]->getZip02())
                                ->setPref(is_null($TargetCustomer[0]->getPref()) ? null : $TargetCustomer[0]->getPref())
                                ->setAddr01($TargetCustomer[0]->getAddr01())
                                ->setAddr02($TargetCustomer[0]->getAddr02())
                                ->setEmail($TargetCustomer[0]->getEmail())
                                ->setTel01($TargetCustomer[0]->getTel01())
                                ->setTel02($TargetCustomer[0]->getTel02())
                                ->setTel03($TargetCustomer[0]->getTel03())
                                ->setFax01($TargetCustomer[0]->getFax01())
                                ->setFax02($TargetCustomer[0]->getFax02())
                                ->setFax03($TargetCustomer[0]->getFax03())
                                ->setCompanyName($TargetCustomer[0]->getCompanyName());



                            // 編集前の受注情報を保持
                            $OriginOrder = clone $TargetOrder;
                            $OriginalOrderDetails = new ArrayCollection();

                            foreach ($TargetOrder->getOrderDetails() as $OrderDetail) {
                                $OriginalOrderDetails->add($OrderDetail);
                            }


                            // 入力情報にもとづいて再計算.
                            $this->calculate($app, $TargetOrder);


                            // $BaseInfo = $app['eccube.repository.base_info']->get();

                            // お支払い方法の更新
                            $TargetOrder->setPaymentMethod($TargetOrder->getPayment()->getMethod());

                            // 配送業者・お届け時間の更新
                            $Shippings = $TargetOrder->getShippings();
                            foreach ($Shippings as $Shipping) {
                                $Shipping->setShippingDeliveryName($Shipping->getDelivery()->getName());
                                if (!is_null($Shipping->getDeliveryTime())) {
                                    $Shipping->setShippingDeliveryTime($Shipping->getDeliveryTime()->getDeliveryTime());
                                } else {
                                    $Shipping->setShippingDeliveryTime(null);
                                }

                                $Shipping
                                    ->setName01($TargetCustomer[0]->getName01())
                                    ->setName02($TargetCustomer[0]->getName02())
                                    ->setKana01($TargetCustomer[0]->getKana01())
                                    ->setKana02($TargetCustomer[0]->getKana02())
                                    ->setZip01($TargetCustomer[0]->getZip01())
                                    ->setZip02($TargetCustomer[0]->getZip02())
                                    ->setPref(is_null($TargetCustomer[0]->getPref()) ? null : $TargetCustomer[0]->getPref())
                                    ->setAddr01($TargetCustomer[0]->getAddr01())
                                    ->setAddr02($TargetCustomer[0]->getAddr02())
                                    ->setTel01($TargetCustomer[0]->getTel01())
                                    ->setTel02($TargetCustomer[0]->getTel02())
                                    ->setTel03($TargetCustomer[0]->getTel03())
                                    ->setFax01($TargetCustomer[0]->getFax01())
                                    ->setFax02($TargetCustomer[0]->getFax02())
                                    ->setFax03($TargetCustomer[0]->getFax03())
                                    ->setCompanyName($TargetCustomer[0]->getCompanyName());


                            }


                            // 受注日/発送日/入金日の更新.
                            $this->updateDate($app, $TargetOrder, $OriginOrder);

                            // // 受注明細で削除されているものをremove
                            // foreach ($OriginalOrderDetails as $OrderDetail) {
                            //     if (false === $TargetOrder->getOrderDetails()->contains($OrderDetail)) {
                            //         $app['orm.em']->remove($OrderDetail);
                            //     }
                            // }


                            if ($BaseInfo->getOptionMultipleShipping() == Constant::ENABLED) {
                                foreach ($TargetOrder->getOrderDetails() as $OrderDetail) {
                                    /** @var $OrderDetail \Eccube\Entity\OrderDetail */
                                    $OrderDetail->setOrder($TargetOrder);
                                }

                                /** @var \Eccube\Entity\Shipping $Shipping */
                                foreach ($Shippings as $Shipping) {
                                    $shipmentItems = $Shipping->getShipmentItems();
                                    /** @var \Eccube\Entity\ShipmentItem $ShipmentItem */
                                    foreach ($shipmentItems as $ShipmentItem) {
                                        $ShipmentItem->setOrder($TargetOrder);
                                        $ShipmentItem->setShipping($Shipping);
                                        $app['orm.em']->persist($ShipmentItem);
                                    }
                                    $Shipping->setOrder($TargetOrder);
                                    $app['orm.em']->persist($Shipping);
                                }
                            } else {

                                $NewShipmentItems = new ArrayCollection();

                                foreach ($TargetOrder->getOrderDetails() as $OrderDetail) {
                                    /** @var $OrderDetail \Eccube\Entity\OrderDetail */
                                    $OrderDetail->setOrder($TargetOrder);

                                    $NewShipmentItem = new ShipmentItem();
                                    $NewShipmentItem
                                        ->setProduct($OrderDetail->getProduct())
                                        ->setProductClass($OrderDetail->getProductClass())
                                        ->setProductName($OrderDetail->getProduct()->getName())
                                        ->setProductCode($OrderDetail->getProductClass()->getCode())
                                        ->setClassCategoryName1($OrderDetail->getClassCategoryName1())
                                        ->setClassCategoryName2($OrderDetail->getClassCategoryName2())
                                        ->setClassName1($OrderDetail->getClassName1())
                                        ->setClassName2($OrderDetail->getClassName2())
                                        ->setPrice($OrderDetail->getPrice())
                                        ->setQuantity($OrderDetail->getQuantity())
                                        ->setOrder($TargetOrder);
                                    $NewShipmentItems[] = $NewShipmentItem;

                                }
                                // 配送商品の更新. delete/insert.
                                $Shippings = $TargetOrder->getShippings();
                                foreach ($Shippings as $Shipping) {
                                    $ShipmentItems = $Shipping->getShipmentItems();
                                    foreach ($ShipmentItems as $ShipmentItem) {
                                        $app['orm.em']->remove($ShipmentItem);
                                    }
                                    $ShipmentItems->clear();
                                    foreach ($NewShipmentItems as $NewShipmentItem) {
                                        $NewShipmentItem->setShipping($Shipping);
                                        $ShipmentItems->add($NewShipmentItem);
                                    }
                                }
                            }

                            $app['orm.em']->persist($TargetOrder);
                            $app['orm.em']->flush();

                            $Customer = $TargetOrder->getCustomer();
                            if ($Customer) {
                                // 会員の場合、購入回数、購入金額などを更新
                                $app['eccube.repository.customer']->updateBuyData($app, $Customer, $TargetOrder->getOrderStatus()->getId());
                            }




                                //return $app->redirect($app->url('admin_order_edit', array('id' => $TargetOrder->getId())));
                            //連携会員ID
                            $refid = $row['連携注文ID'];

                            if ($refid == '') {
                                //何もしない
                            } else {
                                $DownloadProductOrder = $app['eccube.plugin.downloadproduct.repository.downloadproductorder']
                                    ->find($refid);
                                if (!$DownloadProductOrder) {
                                    $DownloadProductOrder=$app['eccube.plugin.downloadproduct.repository.downloadproductorder']
                                                        ->create($refid,$TargetOrder);
                                }else{
                                    $DownloadProductOrder->setOrder($TargetOrder);
                                    $this->em->persist($DownloadProductOrder);

                                }
                               

                            }         
                        }

                        if($del_flg==1){
                                foreach($TargetOrder->getShippings() as $currshp){
                                    $app['orm.em']->remove($currshp);
                                    $app['orm.em']->flush();

                                }

                                foreach($TargetOrder->getOrderDetails() as $currod){
                                    $app['orm.em']->remove($currod);
                                    $app['orm.em']->flush();


                                }
                                $app['orm.em']->remove($DownloadProductOrder);
                                $app['orm.em']->flush();

                                
                                $app['orm.em']->remove($TargetOrder);
                                $app['orm.em']->flush();


                        }


                        if ($this->hasErrors()) {
                            return $this->render($app, $form, $headers, $this->orderTwig);
                        }





                    }

                    $this->em->flush();
                    $this->em->getConnection()->commit();

                    //log_info('会員CSV登録完了');

                    $app->addSuccess('admin.downloadproduct.order.csv_import.save.complete', 'admin');
                }

            }
        }
        return $this->render($app, $form, $headers, $this->orderTwig);
    }





    /**
     * 顧客情報を検索する.
     *
     * @param Application $app
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function searchCustomerById(Application $app, Request $request)
    {
        if ($request->isXmlHttpRequest()) {
            $app['monolog']->addDebug('search customer by id start.');

            /** @var $Customer \Eccube\Entity\Customer */
            $Customer = $app['eccube.repository.customer']
                ->find($request->get('id'));

            $event = new EventArgs(
                array(
                    'Customer' => $Customer,
                ),
                $request
            );
            $app['eccube.event.dispatcher']->dispatch(EccubeEvents::ADMIN_ORDER_EDIT_SEARCH_CUSTOMER_BY_ID_INITIALIZE, $event);

            if (is_null($Customer)) {
                $app['monolog']->addDebug('search customer by id not found.');

                return $app->json(array(), 404);
            }

            $app['monolog']->addDebug('search customer by id found.');

            $data = array(
                'id' => $Customer->getId(),
                'name01' => $Customer->getName01(),
                'name02' => $Customer->getName02(),
                'kana01' => $Customer->getKana01(),
                'kana02' => $Customer->getKana02(),
                'zip01' => $Customer->getZip01(),
                'zip02' => $Customer->getZip02(),
                'pref' => is_null($Customer->getPref()) ? null : $Customer->getPref()->getId(),
                'addr01' => $Customer->getAddr01(),
                'addr02' => $Customer->getAddr02(),
                'email' => $Customer->getEmail(),
                'tel01' => $Customer->getTel01(),
                'tel02' => $Customer->getTel02(),
                'tel03' => $Customer->getTel03(),
                'fax01' => $Customer->getFax01(),
                'fax02' => $Customer->getFax02(),
                'fax03' => $Customer->getFax03(),
                'company_name' => $Customer->getCompanyName(),
            );

            $event = new EventArgs(
                array(
                    'data' => $data,
                    'Customer' => $Customer,
                ),
                $request
            );
            $app['eccube.event.dispatcher']->dispatch(EccubeEvents::ADMIN_ORDER_EDIT_SEARCH_CUSTOMER_BY_ID_COMPLETE, $event);
            $data = $event->getArgument('data');

            return $app->json($data);
        }
    }

    public function searchProduct(Application $app, Request $request)
    {
        if ($request->isXmlHttpRequest()) {
            $app['monolog']->addDebug('search product start.');

            $searchData = array(
                'name' => $request->get('id'),
            );

            if ($categoryId = $request->get('category_id')) {
                $Category = $app['eccube.repository.category']->find($categoryId);
                $searchData['category_id'] = $Category;
            }

            /** @var $Products \Eccube\Entity\Product[] */
            $qb = $app['eccube.repository.product']
                ->getQueryBuilderBySearchData($searchData);

            $event = new EventArgs(
                array(
                    'qb' => $qb,
                    'searchData' => $searchData,
                ),
                $request
            );
            $app['eccube.event.dispatcher']->dispatch(EccubeEvents::ADMIN_ORDER_EDIT_SEARCH_PRODUCT_SEARCH, $event);

            /** @var $Products \Eccube\Entity\Product[] */
            $Products = $qb->getQuery()->getResult();

            if (empty($Products)) {
                $app['monolog']->addDebug('search product not found.');
            }

            $forms = array();
            foreach ($Products as $Product) {
                /* @var $builder \Symfony\Component\Form\FormBuilderInterface */
                $builder = $app['form.factory']->createNamedBuilder('', 'add_cart', null, array(
                    'product' => $Product,
                ));
                $addCartForm = $builder->getForm();
                $forms[$Product->getId()] = $addCartForm->createView();
            }

            $event = new EventArgs(
                array(
                    'forms' => $forms,
                    'Products' => $Products,
                ),
                $request
            );
            $app['eccube.event.dispatcher']->dispatch(EccubeEvents::ADMIN_ORDER_EDIT_SEARCH_PRODUCT_COMPLETE, $event);

            return $app->render('Order/search_product.twig', array(
                'forms' => $forms,
                'Products' => $Products,
            ));
        }
    }

    protected function newOrder()
    {
        $Order = new \Eccube\Entity\Order();
        $OrderStatus = $this->app['eccube.repository.master.order_status']
        ->find($this->app['config']['DownloadProduct']['const']['settingdata']['order']['defaultorderstatus']);

        //new \Eccube\Entity\Master\OrderStatus();
        //$OrderStatus->setId(5);
        $Order->setOrderStatus($OrderStatus);
        $Payment = $this->app['eccube.repository.payment']->find($this->app['config']['DownloadProduct']['const']['settingdata']['order']['defaultpayment']);
        $Order->setPayment($Payment);


        $Order->setCreateDate(new \Datetime('now'));
        $Order->setUpdateDate(new \Datetime('now'));
        $Shipping = new \Eccube\Entity\Shipping();

        $Delivery = $this->app['eccube.repository.delivery']->find($this->app['config']['DownloadProduct']['const']['settingdata']['order']['defaultdelivery']);

        $Shipping->setDelFlg(0);
        $Shipping->setDelivery($Delivery);
        $Order->addShipping($Shipping);
        $Shipping->setOrder($Order);

        return $Order;
    }

    /**
     * フォームからの入直内容に基づいて、受注情報の再計算を行う
     *
     * @param $app
     * @param $Order
     */
    protected function calculate($app, \Eccube\Entity\Order $Order)
    {
        $taxtotal = 0;
        $subtotal = 0;

        // 受注明細データの税・小計を再計算
        /** @var $OrderDetails \Eccube\Entity\OrderDetail[] */
        $OrderDetails = $Order->getOrderDetails();
        foreach ($OrderDetails as $OrderDetail) {
            // 新規登録の場合は, 入力されたproduct_id/produc_class_idから明細にセットする.
            if (!$OrderDetail->getId()) {
                $TaxRule = $app['eccube.repository.tax_rule']->getByRule($OrderDetail->getProduct(),
                    $OrderDetail->getProductClass());
                $OrderDetail->setTaxRule($TaxRule->getCalcRule()->getId());
                $OrderDetail->setProductName($OrderDetail->getProduct()->getName());
                $OrderDetail->setProductCode($OrderDetail->getProductClass()->getCode());
                $OrderDetail->setClassName1($OrderDetail->getProductClass()->hasClassCategory1()
                    ? $OrderDetail->getProductClass()->getClassCategory1()->getClassName()->getName()
                    : null);
                $OrderDetail->setClassName2($OrderDetail->getProductClass()->hasClassCategory2()
                    ? $OrderDetail->getProductClass()->getClassCategory2()->getClassName()->getName()
                    : null);
                $OrderDetail->setClassCategoryName1($OrderDetail->getProductClass()->hasClassCategory1()
                    ? $OrderDetail->getProductClass()->getClassCategory1()->getName()
                    : null);
                $OrderDetail->setClassCategoryName2($OrderDetail->getProductClass()->hasClassCategory2()
                    ? $OrderDetail->getProductClass()->getClassCategory2()->getName()
                    : null);
            }

            // 税
            $tax = $app['eccube.service.tax_rule']
                ->calcTax($OrderDetail->getPrice(), $OrderDetail->getTaxRate(), $OrderDetail->getTaxRule());
            $OrderDetail->setPriceIncTax($OrderDetail->getPrice() + $tax);

            $taxtotal += $tax;

            // 小計
            $subtotal += $OrderDetail->getTotalPrice();
        }

        $shippings = $Order->getShippings();
        /** @var \Eccube\Entity\Shipping $Shipping */
        foreach ($shippings as $Shipping) {
            $shipmentItems = $Shipping->getShipmentItems();
            $Shipping->setDelFlg(Constant::DISABLED);
            /** @var \Eccube\Entity\ShipmentItem $ShipmentItem */
            foreach ($shipmentItems as $ShipmentItem) {
                $ShipmentItem->setProductName($ShipmentItem->getProduct()->getName());
                $ShipmentItem->setProductCode($ShipmentItem->getProductClass()->getCode());
                $ShipmentItem->setClassName1($ShipmentItem->getProductClass()->hasClassCategory1()
                    ? $ShipmentItem->getProductClass()->getClassCategory1()->getClassName()->getName()
                    : null);
                $ShipmentItem->setClassName2($ShipmentItem->getProductClass()->hasClassCategory2()
                    ? $ShipmentItem->getProductClass()->getClassCategory2()->getClassName()->getName()
                    : null);
                $ShipmentItem->setClassCategoryName1($ShipmentItem->getProductClass()->hasClassCategory1()
                    ? $ShipmentItem->getProductClass()->getClassCategory1()->getName()
                    : null);
                $ShipmentItem->setClassCategoryName2($ShipmentItem->getProductClass()->hasClassCategory2()
                    ? $ShipmentItem->getProductClass()->getClassCategory2()->getName()
                    : null);
            }
        }

        // 受注データの税・小計・合計を再計算
        $Order->setTax($taxtotal);
        $Order->setSubtotal($subtotal);
        $Order->setTotal($subtotal + $Order->getCharge() + $Order->getDeliveryFeeTotal() - $Order->getDiscount());
        // お支払い合計は、totalと同一金額(2系ではtotal - point)
        $Order->setPaymentTotal($Order->getTotal());
    }

    /**
     * 受注ステータスに応じて, 受注日/入金日/発送日を更新する,
     * 発送済ステータスが設定された場合は, お届け先情報の発送日も更新を行う.
     *
     * 編集の場合
     * - 受注ステータスが他のステータスから発送済へ変更された場合に発送日を更新
     * - 受注ステータスが他のステータスから入金済へ変更された場合に入金日を更新
     *
     * 新規登録の場合
     * - 受注日を更新
     * - 受注ステータスが発送済に設定された場合に発送日を更新
     * - 受注ステータスが入金済に設定された場合に入金日を更新
     *
     *
     * @param $app
     * @param $TargetOrder
     * @param $OriginOrder
     */
    protected function updateDate($app, $TargetOrder, $OriginOrder)
    {
        $dateTime = new \DateTime();

        // 編集
        if ($TargetOrder->getId()) {
            // 発送済
            if ($TargetOrder->getOrderStatus()->getId() == $app['config']['order_deliv']) {
                // 編集前と異なる場合のみ更新
                if ($TargetOrder->getOrderStatus()->getId() != $OriginOrder->getOrderStatus()->getId()) {
                    $TargetOrder->setCommitDate($dateTime);
                    // お届け先情報の発送日も更新する.
                    $Shippings = $TargetOrder->getShippings();
                    foreach ($Shippings as $Shipping) {
                        $Shipping->setShippingCommitDate($dateTime);
                    }
                }
                // 入金済
            } elseif ($TargetOrder->getOrderStatus()->getId() == $app['config']['order_pre_end']) {
                // 編集前と異なる場合のみ更新
                if ($TargetOrder->getOrderStatus()->getId() != $OriginOrder->getOrderStatus()->getId()) {
                    $TargetOrder->setPaymentDate($dateTime);
                }
            }
            // 新規
        } else {
            // 発送済
            if ($TargetOrder->getOrderStatus()->getId() == $app['config']['order_deliv']) {
                $TargetOrder->setCommitDate($dateTime);
                // お届け先情報の発送日も更新する.
                $Shippings = $TargetOrder->getShippings();
                foreach ($Shippings as $Shipping) {
                    $Shipping->setShippingCommitDate($dateTime);
                }
                // 入金済
            } elseif ($TargetOrder->getOrderStatus()->getId() == $app['config']['order_pre_end']) {
                $TargetOrder->setPaymentDate($dateTime);
            }
            // 受注日時
            //$TargetOrder->setOrderDate($dateTime);
        }
    }




}
