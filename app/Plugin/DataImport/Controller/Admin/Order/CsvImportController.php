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


namespace Plugin\DataImport\Controller\Admin\Order;

use Eccube\Application;
use Eccube\Common\Constant;
use Eccube\Entity\Order;
//use Plugin\DataImport\Entity\CustomerTag;
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
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class CsvImportController extends \Plugin\DataImport\Controller\Base\CsvImportController
{


    private $orderTwig = 'DataImport/Resource/template/admin/Order/csv_order.twig';



    /**
     * 会員登録CSVアップロード
     */
    public function csvCustomer(Application $app, Request $request)
    {
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
dump($data);//die();
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

                        $id = $row['会員ID'];
dump('a');
                        if ($id == '') {
                            $Customer = $app['eccube.repository.customer']->newCustomer();
                            $CustomerAddress = new \Eccube\Entity\CustomerAddress();
                            $Customer->setBuyTimes(0);
                            $Customer->setBuyTotal(0);

                            $this->em->persist($Customer);
                        } else {
                            if (preg_match('/^\d+$/', $row['会員ID'])) {
                                $Customer = $app['orm.em']
                                    ->getRepository('Eccube\Entity\Customer')
                                    ->find($id);
                                if (!$Customer) {
                                    $this->addErrors(($data->key() + 1) . '行目の会員IDが存在しません。');
                                    return $this->render($app, $form, $headers, $this->orderTwig);
                                }
                                 // 編集用にデフォルトパスワードをセット
                                $previous_password = $Customer->getPassword();
                                $Customer->setPassword($app['config']['default_password']);
                               
                            } else {
                                $this->addErrors(($data->key() + 1) . '行目の会員IDが存在しません。');
                                return $this->render($app, $form, $headers, $this->orderTwig);
                            }

                        }

                        if ($row['会員ステータス'] == '') {
                            $this->addErrors(($data->key() + 1) . '行目の会員ステータスが設定されていません。');
                        } else {
                            if (preg_match('/^\d+$/', $row['会員ステータス'])) {
                                $CustomerStatus = $app['eccube.repository.customer_status']->find($row['会員ステータス']);
                                if (!$CustomerStatus) {
                                    $this->addErrors(($data->key() + 1) . '行目の会員ステータスが存在しません。');
                                } else {
                                    $Customer->setStatus($CustomerStatus);
                                }
                            } else {
                                $this->addErrors(($data->key() + 1) . '行目の会員ステータスが存在しません。');
                            }
                        }


                        $key= '会員名１';
                        if (Str::isBlank($row[$key])) {
                            $this->addErrors(($data->key() + 1) . "行目の{$key}が設定されていません。");
                            return $this->render($app, $form, $headers, $this->orderTwig);
                        } else {
                            $Customer->setName01(Str::trimAll($row[$key]));
                        }
                        $key= '会員名2';
                        if (Str::isBlank($row[$key])) {
                            $this->addErrors(($data->key() + 1) . "行目の{$key}が設定されていません。");
                            return $this->render($app, $form, $headers, $this->orderTwig);
                        } else {
                            $Customer->setName02(Str::trimAll($row[$key]));
                        }
                        $key= '会員カナ１';
                        if (Str::isBlank($row[$key])) {
                            $this->addErrors(($data->key() + 1) . "行目の{$key}が設定されていません。");
                            return $this->render($app, $form, $headers, $this->orderTwig);
                        } else {
                            $Customer->setKana01(Str::trimAll($row[$key]));
                        }
                        $key= '会員カナ2';
                        if (Str::isBlank($row[$key])) {
                            $this->addErrors(($data->key() + 1) . "行目の{$key}が設定されていません。");
                            return $this->render($app, $form, $headers, $this->orderTwig);
                        } else {
                            $Customer->setKana02(Str::trimAll($row[$key]));
                        }
                        $key= '会社名';
                        if (Str::isBlank($row[$key])) {
                            $this->addErrors(($data->key() + 1) . "行目の{$key}が設定されていません。");
                            return $this->render($app, $form, $headers, $this->orderTwig);
                        } else {
                            $Customer->setCompanyName(Str::trimAll($row[$key]));
                        }

                        $key= '郵便番号１';
                        if (Str::isBlank($row[$key])) {
                            $this->addErrors(($data->key() + 1) . "行目の{$key}が設定されていません。");
                            return $this->render($app, $form, $headers, $this->orderTwig);
                        } else {
                            $zip = str_replace(',', '', $row[$key]);
                            if (preg_match('/^\d+$/', $zip) ) {
                                $Customer->setZip01(Str::trimAll($row[$key]));
                            } else {
                                $this->addErrors(($data->key() + 1) . "行目の{$key}は数字を設定してください。");
                                return $this->render($app, $form, $headers, $this->orderTwig);
                            }
                        }
                        $key= '郵便番号２';
                        if (Str::isBlank($row[$key])) {
                            $this->addErrors(($data->key() + 1) . "行目の{$key}が設定されていません。");
                            return $this->render($app, $form, $headers, $this->orderTwig);
                        } else {
                            $zip = str_replace(',', '', $row[$key]);
                            if (preg_match('/^\d+$/', $zip) ) {
                                $Customer->setZip02(Str::trimAll($row[$key]));
                            } else {
                                $this->addErrors(($data->key() + 1) . "行目の{$key}は数字を設定してください。");
                                return $this->render($app, $form, $headers, $this->orderTwig);
                            }
                        }
                        $Customer->setZipcode(Str::trimAll($row['郵便番号１']).Str::trimAll($row['郵便番号２']));


                        $key= '都道府県';
                        if (Str::isBlank($row[$key])) {
                            $this->addErrors(($data->key() + 1) . "行目の{$key}が設定されていません。");
                            return $this->render($app, $form, $headers, $this->orderTwig);
                        } else {
                            $prefstr = str_replace(',', '', $row[$key]);
                            $Pref = $app['eccube.repository.master.pref']->findOneBy(array('name'=>$row[$key]));
                            if ($Pref) {
                                $Customer->setPref($Pref);
                            } else {
                                $this->addErrors(($data->key() + 1) . "行目の{$key}は存在する都道府県名を設定してください。");
                                return $this->render($app, $form, $headers, $this->orderTwig);
                            }
                        }

                        $key= '住所１';
                        if (Str::isBlank($row[$key])) {
                            $this->addErrors(($data->key() + 1) . "行目の{$key}が設定されていません。");
                            return $this->render($app, $form, $headers, $this->orderTwig);
                        } else {
                            $Customer->setAddr01(Str::trimAll($row[$key]));
                        }
                        $key= '住所２';
                        if (Str::isBlank($row[$key])) {
                            $this->addErrors(($data->key() + 1) . "行目の{$key}が設定されていません。");
                            return $this->render($app, $form, $headers, $this->orderTwig);
                        } else {
                            $Customer->setAddr02(Str::trimAll($row[$key]));
                        }
                        $key= 'メール';
                        if (Str::isBlank($row[$key])) {
                            $this->addErrors(($data->key() + 1) . "行目の{$key}が設定されていません。");
                            return $this->render($app, $form, $headers, $this->orderTwig);
                        } else {
                            $Customer->setEmail(Str::trimAll($row[$key]));
                        }

                        $keys= array('電話１','電話２','電話３');
                        foreach($keys as $key){
                            if (Str::isBlank($row[$key])) {
                                $this->addErrors(($data->key() + 1) . "行目の{$key}が設定されていません。");
                                return $this->render($app, $form, $headers, $this->orderTwig);
                            } else {
                                $tel = str_replace(',', '', $row[$key]);
                                if (preg_match('/^\d+$/', $tel) ) {
                                    if($key=='電話１'){
                                        $Customer->setTel01(Str::trimAll($row[$key]));
                                    }
                                    if($key=='電話２'){
                                        $Customer->setTel02(Str::trimAll($row[$key]));
                                    }
                                    if($key=='電話３'){
                                        $Customer->setTel03(Str::trimAll($row[$key]));
                                    }
                                } else {
                                    $this->addErrors(($data->key() + 1) . "行目の{$key}は数字を設定してください。");
                                    return $this->render($app, $form, $headers, $this->orderTwig);
                                }
                            }

                        }

                        $keys= array('ＦＡＸ１','ＦＡＸ２','ＦＡＸ３');
                        foreach($keys as $key){
                            if (Str::isBlank($row[$key])) {
                                if($key=='ＦＡＸ１'){
                                    $Customer->setFax01(null);
                                }
                                if($key=='ＦＡＸ２'){
                                    $Customer->setFax02(null);
                                }
                                if($key=='ＦＡＸ３'){
                                    $Customer->setFax03(null);
                                }
                            }else{
                                $tel = str_replace(',', '', $row[$key]);
                                if (preg_match('/^\d+$/', $tel) ) {
                                    if($key=='ＦＡＸ１'){
                                        $Customer->setFax01(Str::trimAll($row[$key]));
                                    }
                                    if($key=='ＦＡＸ２'){
                                        $Customer->setFax02(Str::trimAll($row[$key]));
                                    }
                                    if($key=='ＦＡＸ３'){
                                        $Customer->setFax03(Str::trimAll($row[$key]));
                                    }
                                } else {
                                    $this->addErrors(($data->key() + 1) . "行目の{$key}は数字を設定してください。");
                                    return $this->render($app, $form, $headers, $this->orderTwig);
                                }
                            }
                        }
                        $key= 'メール';
                        if (Str::isBlank($row[$key])) {
                            $this->addErrors(($data->key() + 1) . "行目の{$key}が設定されていません。");
                            return $this->render($app, $form, $headers, $this->orderTwig);
                        } else {
                            if($Customer->getId()==null){
                                $Customermail = $app['orm.em']
                                    ->getRepository('Eccube\Entity\Customer')
                                    ->findOneBy(array('email'=>Str::trimAll($row[$key]),'del_flg'=>0));
                                if ($Customermail) {
                                    $this->addErrors(($data->key() + 1) . '行目のメールアドレスで会員情報が登録済です。');
                                    return $this->render($app, $form, $headers, $this->orderTwig);
                                }


                            }
                            $Customer->setEmail(Str::trimAll($row[$key]));
                        }


                        $key= '性別';
                        if (Str::isBlank($row[$key])) {
                            $this->addErrors(($data->key() + 1) . "行目の{$key}が設定されていません。");
                            return $this->render($app, $form, $headers, $this->orderTwig);
                        } else {
                            $sex = str_replace(',', '', $row[$key]);
                            $Sex = $app['eccube.repository.master.sex']->find($sex);
                            if ($Sex) {
                                $Customer->setSex($Sex);
                            } else {
                                $this->addErrors(($data->key() + 1) . "行目の{$key}は存在する性別IDを設定してください。");
                                return $this->render($app, $form, $headers, $this->orderTwig);
                            }
                        }

                        $key= '生年月日';
                        if (Str::isBlank($row[$key])) {
                            //
                        } else {
                            $birth = Str::trimAll($row[$key]);
                            $Birth = new \Datetime($birth);
                            if ($Birth) {
                                $Customer->setBirth($Birth);
                            } else {
                                $this->addErrors(($data->key() + 1) . '行目の{$key}の形式が正しくありません。');
                                return $this->render($app, $form, $headers, $this->orderTwig);
                            }
                        }

dump('ab');
                        $key= 'パスワード';
                        if (Str::isBlank($row[$key])) {
                            //
                        } else {
                            $Customer->setPassword(Str::trimAll($row[$key]));
                        }




                        if (Str::isNotBlank($row['ショップ用メモ欄'])) {
                            $Customer->setNote(Str::trimAll($row['ショップ用メモ欄']));
                        } else {
                            $Customer->setNote(null);
                        }

                        if ($row['削除フラグ'] == '') {
                            $Customer->setDelFlg(Constant::DISABLED);
                        } else {
                            if ($row['削除フラグ'] == (string)Constant::DISABLED || $row['削除フラグ'] == (string)Constant::ENABLED) {
                                $Customer->setDelFlg($row['削除フラグ']);
                            } else {
                                $this->addErrors(($data->key() + 1) . '行目の削除フラグが設定されていません。');
                                return $this->render($app, $form, $headers, $this->orderTwig);
                            }
                        }

                        if ($Customer->getId() === null) {
                            $Customer->setSalt(
                                $app['eccube.repository.customer']->createSalt(5)
                            );
                            $Customer->setSecretKey(
                                $app['eccube.repository.customer']->getUniqueSecretKey($app)
                            );

                        }

                        if ($Customer->getPassword() === $app['config']['default_password']) {
                            $Customer->setPassword($previous_password);
                        } else {
                            if ($Customer->getSalt() === null) {
                                $Customer->setSalt($app['eccube.repository.customer']->createSalt(5));
                            }
                            $Customer->setPassword(
                                $app['eccube.repository.customer']->encryptPassword($app, $Customer)
                            );
                        }

                        $this->em->persist($Customer);

                        $this->em->flush($Customer);

                        $CustomerAddress->setName01($Customer->getName01())
                            ->setName02($Customer->getName02())
                            ->setKana01($Customer->getKana01())
                            ->setKana02($Customer->getKana02())
                            ->setCompanyName($Customer->getCompanyName())
                            ->setZip01($Customer->getZip01())
                            ->setZip02($Customer->getZip02())
                            ->setZipcode($Customer->getZip01() . $Customer->getZip02())
                            ->setPref($Customer->getPref())
                            ->setAddr01($Customer->getAddr01())
                            ->setAddr02($Customer->getAddr02())
                            ->setTel01($Customer->getTel01())
                            ->setTel02($Customer->getTel02())
                            ->setTel03($Customer->getTel03())
                            ->setFax01($Customer->getFax01())
                            ->setFax02($Customer->getFax02())
                            ->setFax03($Customer->getFax03())
                            ->setDelFlg(Constant::DISABLED)
                            ->setCustomer($Customer);

                        $this->em->persist($CustomerAddress);

                        //会員タグ
                        //メルマガフラグ
                        //

dump($Customer);
dump($CustomerAddress);

                        if ($this->hasErrors()) {
                            return $this->render($app, $form, $headers, $this->orderTwig);
                        }





                    }

                    $this->em->flush();
                    $this->em->getConnection()->commit();

                    //log_info('会員CSV登録完了');

                    $app->addSuccess('admin.dataimport.order.csv_import.save.complete', 'admin');
                }

            }
        }

        return $this->render($app, $form, $headers, $this->orderTwig);
    }



    //Order\EditControllerから流用


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
        $Shipping = new \Eccube\Entity\Shipping();
        $Shipping->setDelFlg(0);
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
            $TargetOrder->setOrderDate($dateTime);
        }
    }




}
