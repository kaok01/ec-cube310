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


namespace Plugin\DataImport\Controller\Admin\Customer;

use Eccube\Application;
use Eccube\Common\Constant;
use Eccube\Entity\Customer;
//use Plugin\DataImport\Entity\CustomerTag;
use Eccube\Exception\CsvImportException;
use Eccube\Service\CsvImportService;
use Eccube\Util\Str;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class CsvImportController extends \Plugin\DataImport\Controller\Base\CsvImportController
{


    private $customerTwig = 'DataImport/Resource/template/admin/Customer/csv_customer.twig';



    /**
     * 会員登録CSVアップロード
     */
    public function csvCustomer(Application $app, Request $request)
    {
        $form = $app['form.factory']->createBuilder('admin_csv_import')->getForm();

        $headers = $this->getCustomerCsvHeader();

        if ('POST' === $request->getMethod()) {

            $form->handleRequest($request);

            if ($form->isValid()) {

                $formFile = $form['import_file']->getData();

                if (!empty($formFile)) {

                    //log_info('会員CSV登録開始');

                    $data = $this->getImportData($app, $formFile);
                    if ($data === false) {
                        $this->addErrors('CSVのフォーマットが一致しません。');
                        return $this->render($app, $form, $headers, $this->customerTwig);
                    }

                    $keys = array_keys($headers);
                    $columnHeaders = $data->getColumnHeaders();
                    if ($keys !== $columnHeaders) {
                        $this->addErrors('CSVのフォーマットが一致しません。');
                        return $this->render($app, $form, $headers, $this->customerTwig);
                    }

                    $size = count($data);
                    if ($size < 1) {
                        $this->addErrors('CSVデータが存在しません。');
                        return $this->render($app, $form, $headers, $this->productTwig);
                    }

                    $headerSize = count($keys);

                    $this->em = $app['orm.em'];
                    $this->em->getConfiguration()->setSQLLogger(null);

                    $this->em->getConnection()->beginTransaction();

                    $BaseInfo = $app['eccube.repository.base_info']->get();

                    // CSVファイルの登録処理
                    foreach ($data as $row) {

                        if ($headerSize != count($row)) {
                            $this->addErrors(($data->key() + 1) . '行目のCSVフォーマットが一致しません。');
                            return $this->render($app, $form, $headers, $this->productTwig);
                        }

                        if ($row['商品ID'] == '') {
                            $Product = new Product();
                            $this->em->persist($Product);
                        } else {
                            if (preg_match('/^\d+$/', $row['商品ID'])) {
                                $Product = $app['eccube.repository.product']->find($row['商品ID']);
                                if (!$Product) {
                                    $this->addErrors(($data->key() + 1) . '行目の商品IDが存在しません。');
                                    return $this->render($app, $form, $headers, $this->productTwig);
                                }
                            } else {
                                $this->addErrors(($data->key() + 1) . '行目の商品IDが存在しません。');
                                return $this->render($app, $form, $headers, $this->productTwig);
                            }

                        }

                        if ($row['公開ステータス(ID)'] == '') {
                            $this->addErrors(($data->key() + 1) . '行目の公開ステータス(ID)が設定されていません。');
                        } else {
                            if (preg_match('/^\d+$/', $row['公開ステータス(ID)'])) {
                                $Disp = $app['eccube.repository.master.disp']->find($row['公開ステータス(ID)']);
                                if (!$Disp) {
                                    $this->addErrors(($data->key() + 1) . '行目の公開ステータス(ID)が存在しません。');
                                } else {
                                    $Product->setStatus($Disp);
                                }
                            } else {
                                $this->addErrors(($data->key() + 1) . '行目の公開ステータス(ID)が存在しません。');
                            }
                        }

                        if (Str::isBlank($row['商品名'])) {
                            $this->addErrors(($data->key() + 1) . '行目の商品名が設定されていません。');
                            return $this->render($app, $form, $headers, $this->productTwig);
                        } else {
                            $Product->setName(Str::trimAll($row['商品名']));
                        }

                        if (Str::isNotBlank($row['ショップ用メモ欄'])) {
                            $Product->setNote(Str::trimAll($row['ショップ用メモ欄']));
                        } else {
                            $Product->setNote(null);
                        }

                        if (Str::isNotBlank($row['商品説明(一覧)'])) {
                            $Product->setDescriptionList(Str::trimAll($row['商品説明(一覧)']));
                        } else {
                            $Product->setDescriptionList(null);
                        }

                        if (Str::isNotBlank($row['商品説明(詳細)'])) {
                            $Product->setDescriptionDetail(Str::trimAll($row['商品説明(詳細)']));
                        } else {
                            $Product->setDescriptionDetail(null);
                        }

                        if (Str::isNotBlank($row['検索ワード'])) {
                            $Product->setSearchWord(Str::trimAll($row['検索ワード']));
                        } else {
                            $Product->setSearchWord(null);
                        }

                        if (Str::isNotBlank($row['フリーエリア'])) {
                            $Product->setFreeArea(Str::trimAll($row['フリーエリア']));
                        } else {
                            $Product->setFreeArea(null);
                        }

                        if ($row['商品削除フラグ'] == '') {
                            $Product->setDelFlg(Constant::DISABLED);
                        } else {
                            if ($row['商品削除フラグ'] == (string)Constant::DISABLED || $row['商品削除フラグ'] == (string)Constant::ENABLED) {
                                $Product->setDelFlg($row['商品削除フラグ']);
                            } else {
                                $this->addErrors(($data->key() + 1) . '行目の商品削除フラグが設定されていません。');
                                return $this->render($app, $form, $headers, $this->productTwig);
                            }
                        }

                        // 商品画像登録
                        $this->createProductImage($row, $Product);

                        $this->em->flush($Product);

                        // 商品カテゴリ登録
                        $this->createProductCategory($row, $Product, $app, $data);

                        //タグ登録
                        $this->createProductTag($row, $Product, $app, $data);

                        // 商品規格が存在しなければ新規登録
                        $ProductClasses = $Product->getProductClasses();
                        if ($ProductClasses->count() < 1) {
                            // 規格分類1(ID)がセットされていると規格なし商品、規格あり商品を作成
                            $ProductClassOrg = $this->createProductClass($row, $Product, $app, $data);
                            if ($BaseInfo->getOptionProductDeliveryFee() == Constant::ENABLED) {
                                if ($row['送料'] != '') {
                                    $deliveryFee = str_replace(',', '', $row['送料']);
                                    if (preg_match('/^\d+$/', $deliveryFee) && $deliveryFee >= 0) {
                                        $ProductClassOrg->setDeliveryFee($deliveryFee);
                                    } else {
                                        $this->addErrors(($data->key() + 1) . '行目の送料は0以上の数値を設定してください。');
                                    }
                                }
                            }

                            if ($row['規格分類1(ID)'] != '') {

                                if ($row['規格分類1(ID)'] == $row['規格分類2(ID)']) {
                                    $this->addErrors(($data->key() + 1) . '行目の規格分類1(ID)と規格分類2(ID)には同じ値を使用できません。');
                                } else {
                                    // 商品規格あり
                                    // 企画分類あり商品を作成
                                    $ProductClass = clone $ProductClassOrg;
                                    $ProductStock = clone $ProductClassOrg->getProductStock();

                                    // 規格分類1、規格分類2がnullであるデータの削除フラグを1にセット
                                    $ProductClassOrg->setDelFlg(Constant::ENABLED);

                                    // 規格分類1、2をそれぞれセットし作成
                                    $ClassCategory1 = null;
                                    if (preg_match('/^\d+$/', $row['規格分類1(ID)'])) {
                                        $ClassCategory1 = $app['eccube.repository.class_category']->find($row['規格分類1(ID)']);
                                        if (!$ClassCategory1) {
                                            $this->addErrors(($data->key() + 1) . '行目の規格分類1(ID)が存在しません。');
                                        } else {
                                            $ProductClass->setClassCategory1($ClassCategory1);
                                        }
                                    } else {
                                        $this->addErrors(($data->key() + 1) . '行目の規格分類1(ID)が存在しません。');
                                    }

                                    if ($row['規格分類2(ID)'] != '') {
                                        if (preg_match('/^\d+$/', $row['規格分類2(ID)'])) {
                                            $ClassCategory2 = $app['eccube.repository.class_category']->find($row['規格分類2(ID)']);
                                            if (!$ClassCategory2) {
                                                $this->addErrors(($data->key() + 1) . '行目の規格分類2(ID)が存在しません。');
                                            } else {
                                                if ($ClassCategory1 &&
                                                    ($ClassCategory1->getClassName()->getId() == $ClassCategory2->getClassName()->getId())
                                                ) {
                                                    $this->addErrors(($data->key() + 1) . '行目の規格分類1(ID)と規格分類2(ID)の規格名が同じです。');
                                                } else {
                                                    $ProductClass->setClassCategory2($ClassCategory2);
                                                }
                                            }
                                        } else {
                                            $this->addErrors(($data->key() + 1) . '行目の規格分類2(ID)が存在しません。');
                                        }
                                    }
                                    $ProductClass->setProductStock($ProductStock);
                                    $ProductStock->setProductClass($ProductClass);

                                    $this->em->persist($ProductClass);
                                    $this->em->persist($ProductStock);
                                }

                            } else {
                                if ($row['規格分類2(ID)'] != '') {
                                    $this->addErrors(($data->key() + 1) . '行目の規格分類1(ID)が存在しません。');
                                }
                            }

                        } else {
                            // 商品規格の更新

                            $flag = false;
                            $classCategoryId1 = $row['規格分類1(ID)'] == '' ? null : $row['規格分類1(ID)'];
                            $classCategoryId2 = $row['規格分類2(ID)'] == '' ? null : $row['規格分類2(ID)'];

                            foreach ($ProductClasses as $pc) {

                                $classCategory1 = is_null($pc->getClassCategory1()) ? null : $pc->getClassCategory1()->getId();
                                $classCategory2 = is_null($pc->getClassCategory2()) ? null : $pc->getClassCategory2()->getId();

                                // 登録されている商品規格を更新
                                if ($classCategory1 == $classCategoryId1 &&
                                    $classCategory2 == $classCategoryId2
                                ) {
                                    $this->updateProductClass($row, $Product, $pc, $app, $data);

                                    if ($BaseInfo->getOptionProductDeliveryFee() == Constant::ENABLED) {
                                        if ($row['送料'] != '') {
                                            $deliveryFee = str_replace(',', '', $row['送料']);
                                            if (preg_match('/^\d+$/', $deliveryFee) && $deliveryFee >= 0) {
                                                $pc->setDeliveryFee($deliveryFee);
                                            } else {
                                                $this->addErrors(($data->key() + 1) . '行目の送料は0以上の数値を設定してください。');
                                            }
                                        }
                                    }

                                    $flag = true;
                                    break;
                                }
                            }

                            // 商品規格を登録
                            if (!$flag) {
                                $pc = $ProductClasses[0];
                                if ($pc->getClassCategory1() == null &&
                                    $pc->getClassCategory2() == null
                                ) {

                                    // 規格分類1、規格分類2がnullであるデータの削除フラグを1にセット
                                    $pc->setDelFlg(Constant::ENABLED);
                                }

                                if ($row['規格分類1(ID)'] == $row['規格分類2(ID)']) {
                                    $this->addErrors(($data->key() + 1) . '行目の規格分類1(ID)と規格分類2(ID)には同じ値を使用できません。');
                                } else {

                                    // 必ず規格分類1がセットされている
                                    // 規格分類1、2をそれぞれセットし作成
                                    $ClassCategory1 = null;
                                    if (preg_match('/^\d+$/', $classCategoryId1)) {
                                        $ClassCategory1 = $app['eccube.repository.class_category']->find($classCategoryId1);
                                        if (!$ClassCategory1) {
                                            $this->addErrors(($data->key() + 1) . '行目の規格分類1(ID)が存在しません。');
                                        }
                                    } else {
                                        $this->addErrors(($data->key() + 1) . '行目の規格分類1(ID)が存在しません。');
                                    }

                                    $ClassCategory2 = null;
                                    if ($row['規格分類2(ID)'] != '') {
                                        if ($pc->getClassCategory1() != null && $pc->getClassCategory2() == null) {
                                            $this->addErrors(($data->key() + 1) . '行目の規格分類2(ID)は設定できません。');
                                        } else {
                                            if (preg_match('/^\d+$/', $classCategoryId2)) {
                                                $ClassCategory2 = $app['eccube.repository.class_category']->find($classCategoryId2);
                                                if (!$ClassCategory2) {
                                                    $this->addErrors(($data->key() + 1) . '行目の規格分類2(ID)が存在しません。');
                                                } else {
                                                    if ($ClassCategory1 &&
                                                        ($ClassCategory1->getClassName()->getId() == $ClassCategory2->getClassName()->getId())
                                                    ) {
                                                        $this->addErrors(($data->key() + 1) . '行目の規格分類1(ID)と規格分類2(ID)の規格名が同じです。');
                                                    }
                                                }
                                            } else {
                                                $this->addErrors(($data->key() + 1) . '行目の規格分類2(ID)が存在しません。');
                                            }

                                        }
                                    } else {
                                        if ($pc->getClassCategory1() != null && $pc->getClassCategory2() != null) {
                                            $this->addErrors(($data->key() + 1) . '行目の規格分類2(ID)に値を設定してください。');
                                        }
                                    }
                                    $ProductClass = $this->createProductClass($row, $Product, $app, $data, $ClassCategory1, $ClassCategory2);

                                    if ($BaseInfo->getOptionProductDeliveryFee() == Constant::ENABLED) {
                                        if ($row['送料'] != '') {
                                            $deliveryFee = str_replace(',', '', $row['送料']);
                                            if (preg_match('/^\d+$/', $deliveryFee) && $deliveryFee >= 0) {
                                                $ProductClass->setDeliveryFee($deliveryFee);
                                            } else {
                                                $this->addErrors(($data->key() + 1) . '行目の送料は0以上の数値を設定してください。');
                                            }
                                        }
                                    }

                                    $Product->addProductClass($ProductClass);
                                }

                            }

                        }


                        if ($this->hasErrors()) {
                            return $this->render($app, $form, $headers, $this->productTwig);
                        }

                        $this->em->persist($Product);

                    }

                    $this->em->flush();
                    $this->em->getConnection()->commit();

                    //log_info('会員CSV登録完了');

                    $app->addSuccess('admin.dataimport.customer.csv_import.save.complete', 'admin');
                }

            }
        }

        return $this->render($app, $form, $headers, $this->customerTwig);
    }






    /**
     * 商品画像の削除、登録
     */
    protected function createProductImage($row, Product $Product)
    {
        if ($row['商品画像'] != '') {

            // 画像の削除
            $ProductImages = $Product->getProductImage();
            foreach ($ProductImages as $ProductImage) {
                $Product->removeProductImage($ProductImage);
                $this->em->remove($ProductImage);
            }

            // 画像の登録
            $images = explode(',', $row['商品画像']);
            $rank = 1;
            foreach ($images as $image) {

                $ProductImage = new ProductImage();
                $ProductImage->setFileName(Str::trimAll($image));
                $ProductImage->setProduct($Product);
                $ProductImage->setRank($rank);

                $Product->addProductImage($ProductImage);
                $rank++;
                $this->em->persist($ProductImage);
            }
        }
    }


    /**
     * 商品カテゴリの削除、登録
     */
    protected function createProductCategory($row, Product $Product, $app, $data)
    {
        // カテゴリの削除
        $ProductCategories = $Product->getProductCategories();
        foreach ($ProductCategories as $ProductCategory) {
            $Product->removeProductCategory($ProductCategory);
            $this->em->remove($ProductCategory);
            $this->em->flush($ProductCategory);
        }

        if ($row['商品カテゴリ(ID)'] == '') {
            // 入力されていなければ削除のみ
            return;
        }

        // カテゴリの登録
        $categories = explode(',', $row['商品カテゴリ(ID)']);
        $rank = 1;
        foreach ($categories as $category) {

            if (preg_match('/^\d+$/', $category)) {
                $Category = $app['eccube.repository.category']->find($category);
                if (!$Category) {
                    $this->addErrors(($data->key() + 1).'行目の商品カテゴリ(ID)「'.$category.'」が存在しません。');
                } else {
                    $ProductCategory = new ProductCategory();
                    $ProductCategory->setProductId($Product->getId());
                    $ProductCategory->setCategoryId($Category->getId());
                    $ProductCategory->setProduct($Product);
                    $ProductCategory->setCategory($Category);
                    $ProductCategory->setRank($rank);
                    $Product->addProductCategory($ProductCategory);
                    $rank++;
                    $this->em->persist($ProductCategory);
                }
            } else {
                $this->addErrors(($data->key() + 1).'行目の商品カテゴリ(ID)「'.$category.'」が存在しません。');
            }
        }

    }


    /**
     * タグの登録
     *
     * @param array $row
     * @param Product $Product
     * @param Application $app
     * @param CsvImportService $data
     */
    protected function createProductTag($row, Product $Product, $app, $data)
    {
        // タグの削除
        $ProductTags = $Product->getProductTag();
        foreach ($ProductTags as $ProductTags) {
            $Product->removeProductTag($ProductTags);
            $this->em->remove($ProductTags);
        }

        if ($row['タグ(ID)'] == '') {
            return;
        }

        // タグの登録
        $tags = explode(',', $row['タグ(ID)']);
        foreach ($tags as $tag_id) {
            $Tag = null;
            if (preg_match('/^\d+$/', $tag_id)) {
                $Tag = $app['eccube.repository.master.tag']->find($tag_id);
                if ($Tag) {
                    $ProductTags = new ProductTag();
                    $ProductTags
                        ->setProduct($Product)
                        ->setTag($Tag);

                    $Product->addProductTag($ProductTags);

                    $this->em->persist($ProductTags);
                }
            }
            if (!$Tag) {
                $this->addErrors(($data->key() + 1) . '行目のタグ(ID)「' . $tag_id . '」が存在しません。');
            }
        }
    }


    /**
     * 商品規格分類1、商品規格分類2がnullとなる商品規格情報を作成
     */
    protected function createProductClass($row, Product $Product, $app, $data, $ClassCategory1 = null, $ClassCategory2 = null)
    {
        // 規格分類1、規格分類2がnullとなる商品を作成

        $ProductClass = new ProductClass();
        $ProductClass->setProduct($Product);


        if ($row['商品種別(ID)'] == '') {
            $this->addErrors(($data->key() + 1) . '行目の商品種別(ID)が設定されていません。');
        } else {
            if (preg_match('/^\d+$/', $row['商品種別(ID)'])) {
                $ProductType = $app['eccube.repository.master.product_type']->find($row['商品種別(ID)']);
                if (!$ProductType) {
                    $this->addErrors(($data->key() + 1) . '行目の商品種別(ID)が存在しません。');
                } else {
                    $ProductClass->setProductType($ProductType);
                }
            } else {
                $this->addErrors(($data->key() + 1) . '行目の商品種別(ID)が存在しません。');
            }
        }

        $ProductClass->setClassCategory1($ClassCategory1);
        $ProductClass->setClassCategory2($ClassCategory2);

        if ($row['発送日目安(ID)'] != '') {
            if (preg_match('/^\d+$/', $row['発送日目安(ID)'])) {
                $DeliveryDate = $app['eccube.repository.delivery_date']->find($row['発送日目安(ID)']);
                if (!$DeliveryDate) {
                    $this->addErrors(($data->key() + 1) . '行目の発送日目安(ID)が存在しません。');
                } else {
                    $ProductClass->setDeliveryDate($DeliveryDate);
                }
            } else {
                $this->addErrors(($data->key() + 1) . '行目の発送日目安(ID)が存在しません。');
            }
        }

        if (Str::isNotBlank($row['商品コード'])) {
            $ProductClass->setCode(Str::trimAll($row['商品コード']));
        } else {
            $ProductClass->setCode(null);
        }

        if ($row['在庫数無制限フラグ'] == '') {
            $this->addErrors(($data->key() + 1) . '行目の在庫数無制限フラグが設定されていません。');
        } else {
            if ($row['在庫数無制限フラグ'] == (string) Constant::DISABLED) {
                $ProductClass->setStockUnlimited(Constant::DISABLED);
                // 在庫数が設定されていなければエラー
                if ($row['在庫数'] == '') {
                    $this->addErrors(($data->key() + 1) . '行目の在庫数が設定されていません。');
                } else {
                    $stock = str_replace(',', '', $row['在庫数']);
                    if (preg_match('/^\d+$/', $stock) && $stock >= 0) {
                        $ProductClass->setStock($stock);
                    } else {
                        $this->addErrors(($data->key() + 1) . '行目の在庫数は0以上の数値を設定してください。');
                    }
                }

            } else if ($row['在庫数無制限フラグ'] == (string) Constant::ENABLED) {
                $ProductClass->setStockUnlimited(Constant::ENABLED);
                $ProductClass->setStock(null);
            } else {
                $this->addErrors(($data->key() + 1) . '行目の在庫数無制限フラグが設定されていません。');
            }
        }

        if ($row['販売制限数'] != '') {
            $saleLimit = str_replace(',', '', $row['販売制限数']);
            if (preg_match('/^\d+$/', $saleLimit) && $saleLimit >= 0) {
                $ProductClass->setSaleLimit($saleLimit);
            } else {
                $this->addErrors(($data->key() + 1) . '行目の販売制限数は0以上の数値を設定してください。');
            }
        }

        if ($row['通常価格'] != '') {
            $price01 = str_replace(',', '', $row['通常価格']);
            if (preg_match('/^\d+$/', $price01) && $price01 >= 0) {
                $ProductClass->setPrice01($price01);
            } else {
                $this->addErrors(($data->key() + 1) . '行目の通常価格は0以上の数値を設定してください。');
            }
        }

        if ($row['販売価格'] == '') {
            $this->addErrors(($data->key() + 1) . '行目の販売価格が設定されていません。');
        } else {
            $price02 = str_replace(',', '', $row['販売価格']);
            if (preg_match('/^\d+$/', $price02) && $price02 >= 0) {
                $ProductClass->setPrice02($price02);
            } else {
                $this->addErrors(($data->key() + 1) . '行目の販売価格は0以上の数値を設定してください。');
            }
        }

        if ($row['送料'] != '') {
            $delivery_fee = str_replace(',', '', $row['送料']);
            if (preg_match('/^\d+$/', $delivery_fee) && $delivery_fee >= 0) {
                $ProductClass->setDeliveryFee($delivery_fee);
            } else {
                $this->addErrors(($data->key() + 1) . '行目の送料は0以上の数値を設定してください。');
            }
        }

        if ($row['商品規格削除フラグ'] == '') {
            $ProductClass->setDelFlg(Constant::DISABLED);
        } else {
            if ($row['商品規格削除フラグ'] == (string) Constant::DISABLED || $row['商品規格削除フラグ'] == (string) Constant::ENABLED) {
                $ProductClass->setDelFlg($row['商品規格削除フラグ']);
            } else {
                $this->addErrors(($data->key() + 1) . '行目の商品規格削除フラグが設定されていません。');
            }
        }

        $Product->addProductClass($ProductClass);
        $ProductStock = new ProductStock();
        $ProductClass->setProductStock($ProductStock);
        $ProductStock->setProductClass($ProductClass);

        if (!$ProductClass->getStockUnlimited()) {
            $ProductStock->setStock($ProductClass->getStock());
        } else {
            // 在庫無制限時はnullを設定
            $ProductStock->setStock(null);
        }

        $this->em->persist($ProductClass);
        $this->em->persist($ProductStock);

        return $ProductClass;

    }


    /**
     * 商品規格情報を更新
     */
    protected function updateProductClass($row, Product $Product, ProductClass $ProductClass, $app, $data)
    {

        $ProductClass->setProduct($Product);

        if ($row['商品種別(ID)'] == '') {
            $this->addErrors(($data->key() + 1) . '行目の商品種別(ID)が設定されていません。');
        } else {
            if (preg_match('/^\d+$/', $row['商品種別(ID)'])) {
                $ProductType = $app['eccube.repository.master.product_type']->find($row['商品種別(ID)']);
                if (!$ProductType) {
                    $this->addErrors(($data->key() + 1) . '行目の商品種別(ID)が存在しません。');
                } else {
                    $ProductClass->setProductType($ProductType);
                }
            } else {
                $this->addErrors(($data->key() + 1) . '行目の商品種別(ID)が存在しません。');
            }
        }

        // 規格分類1、2をそれぞれセットし作成
        if ($row['規格分類1(ID)'] != '') {
            if (preg_match('/^\d+$/', $row['規格分類1(ID)'])) {
                $ClassCategory = $app['eccube.repository.class_category']->find($row['規格分類1(ID)']);
                if (!$ClassCategory) {
                    $this->addErrors(($data->key() + 1) . '行目の規格分類1(ID)が存在しません。');
                } else {
                    $ProductClass->setClassCategory1($ClassCategory);
                }
            } else {
                $this->addErrors(($data->key() + 1) . '行目の規格分類1(ID)が存在しません。');
            }
        }

        if ($row['規格分類2(ID)'] != '') {
            if (preg_match('/^\d+$/', $row['規格分類2(ID)'])) {
                $ClassCategory = $app['eccube.repository.class_category']->find($row['規格分類2(ID)']);
                if (!$ClassCategory) {
                    $this->addErrors(($data->key() + 1) . '行目の規格分類2(ID)が存在しません。');
                } else {
                    $ProductClass->setClassCategory2($ClassCategory);
                }
            } else {
                $this->addErrors(($data->key() + 1) . '行目の規格分類2(ID)が存在しません。');
            }
        }

        if ($row['発送日目安(ID)'] != '') {
            if (preg_match('/^\d+$/', $row['発送日目安(ID)'])) {
                $DeliveryDate = $app['eccube.repository.delivery_date']->find($row['発送日目安(ID)']);
                if (!$DeliveryDate) {
                    $this->addErrors(($data->key() + 1) . '行目の発送日目安(ID)が存在しません。');
                } else {
                    $ProductClass->setDeliveryDate($DeliveryDate);
                }
            } else {
                $this->addErrors(($data->key() + 1) . '行目の発送日目安(ID)が存在しません。');
            }
        }

        if (Str::isNotBlank($row['商品コード'])) {
            $ProductClass->setCode(Str::trimAll($row['商品コード']));
        } else {
            $ProductClass->setCode(null);
        }

        if ($row['在庫数無制限フラグ'] == '') {
            $this->addErrors(($data->key() + 1) . '行目の在庫数無制限フラグが設定されていません。');
        } else {
            if ($row['在庫数無制限フラグ'] == (string) Constant::DISABLED) {
                $ProductClass->setStockUnlimited(Constant::DISABLED);
                // 在庫数が設定されていなければエラー
                if ($row['在庫数'] == '') {
                    $this->addErrors(($data->key() + 1) . '行目の在庫数が設定されていません。');
                } else {
                    $stock = str_replace(',', '', $row['在庫数']);
                    if (preg_match('/^\d+$/', $stock) && $stock >= 0) {
                        $ProductClass->setStock($row['在庫数']);
                    } else {
                        $this->addErrors(($data->key() + 1) . '行目の在庫数は0以上の数値を設定してください。');
                    }
                }

            } else if ($row['在庫数無制限フラグ'] == (string) Constant::ENABLED) {
                $ProductClass->setStockUnlimited(Constant::ENABLED);
                $ProductClass->setStock(null);
            } else {
                $this->addErrors(($data->key() + 1) . '行目の在庫数無制限フラグが設定されていません。');
            }
        }

        if ($row['販売制限数'] != '') {
            $saleLimit = str_replace(',', '', $row['販売制限数']);
            if (preg_match('/^\d+$/', $saleLimit) && $saleLimit >= 0) {
                $ProductClass->setSaleLimit($saleLimit);
            } else {
                $this->addErrors(($data->key() + 1) . '行目の販売制限数は0以上の数値を設定してください。');
            }
        }

        if ($row['通常価格'] != '') {
            $price01 = str_replace(',', '', $row['通常価格']);
            if (preg_match('/^\d+$/', $price01) && $price01 >= 0) {
                $ProductClass->setPrice01($price01);
            } else {
                $this->addErrors(($data->key() + 1) . '行目の通常価格は0以上の数値を設定してください。');
            }
        }

        if ($row['販売価格'] == '') {
            $this->addErrors(($data->key() + 1) . '行目の販売価格が設定されていません。');
        } else {
            $price02 = str_replace(',', '', $row['販売価格']);
            if (preg_match('/^\d+$/', $price02) && $price02 >= 0) {
                $ProductClass->setPrice02($price02);
            } else {
                $this->addErrors(($data->key() + 1) . '行目の販売価格は0以上の数値を設定してください。');
            }
        }

        if ($row['商品規格削除フラグ'] == '') {
            $ProductClass->setDelFlg(Constant::DISABLED);
        } else {
            if ($row['商品規格削除フラグ'] == (string) Constant::DISABLED || $row['商品規格削除フラグ'] == (string) Constant::ENABLED) {
                $ProductClass->setDelFlg($row['商品規格削除フラグ']);
            } else {
                $this->addErrors(($data->key() + 1) . '行目の商品規格削除フラグが設定されていません。');
            }
        }

        $ProductStock = $ProductClass->getProductStock();

        if (!$ProductClass->getStockUnlimited()) {
            $ProductStock->setStock($ProductClass->getStock());
        } else {
            // 在庫無制限時はnullを設定
            $ProductStock->setStock(null);
        }

        return $ProductClass;
    }




}
