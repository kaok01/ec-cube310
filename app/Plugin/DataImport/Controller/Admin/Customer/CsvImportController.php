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
use Eccube\Entity\CustomerAddress;
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
                        return $this->render($app, $form, $headers, $this->customerTwig);
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
                            return $this->render($app, $form, $headers, $this->customerTwig);
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
                                    return $this->render($app, $form, $headers, $this->customerTwig);
                                }
                                 // 編集用にデフォルトパスワードをセット
                                $previous_password = $Customer->getPassword();
                                $Customer->setPassword($app['config']['default_password']);
                                $CustomerAddress = $app['orm.em']
                                    ->getRepository('Eccube\Entity\CustomerAddress')
                                    ->findBy(array('Customer'=>$Customer,'del_flg'=>0));
                                if($CustomerAddress){
                                    $CustomerAddress = $CustomerAddress[0];
                                }                               
                            } else {
                                $this->addErrors(($data->key() + 1) . '行目の会員IDが存在しません。');
                                return $this->render($app, $form, $headers, $this->customerTwig);
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
                            return $this->render($app, $form, $headers, $this->customerTwig);
                        } else {
                            $Customer->setName01(Str::trimAll($row[$key]));
                        }
                        $key= '会員名2';
                        if (Str::isBlank($row[$key])) {
                            $this->addErrors(($data->key() + 1) . "行目の{$key}が設定されていません。");
                            return $this->render($app, $form, $headers, $this->customerTwig);
                        } else {
                            $Customer->setName02(Str::trimAll($row[$key]));
                        }
                        $key= '会員カナ１';
                        if (Str::isBlank($row[$key])) {
                            $this->addErrors(($data->key() + 1) . "行目の{$key}が設定されていません。");
                            return $this->render($app, $form, $headers, $this->customerTwig);
                        } else {
                            $Customer->setKana01(Str::trimAll($row[$key]));
                        }
                        $key= '会員カナ2';
                        if (Str::isBlank($row[$key])) {
                            $this->addErrors(($data->key() + 1) . "行目の{$key}が設定されていません。");
                            return $this->render($app, $form, $headers, $this->customerTwig);
                        } else {
                            $Customer->setKana02(Str::trimAll($row[$key]));
                        }
                        $key= '会社名';
                        if (Str::isBlank($row[$key])) {
                            $this->addErrors(($data->key() + 1) . "行目の{$key}が設定されていません。");
                            return $this->render($app, $form, $headers, $this->customerTwig);
                        } else {
                            $Customer->setCompanyName(Str::trimAll($row[$key]));
                        }

                        $key= '郵便番号１';
                        if (Str::isBlank($row[$key])) {
                            $this->addErrors(($data->key() + 1) . "行目の{$key}が設定されていません。");
                            return $this->render($app, $form, $headers, $this->customerTwig);
                        } else {
                            $zip = str_replace(',', '', $row[$key]);
                            if (preg_match('/^\d+$/', $zip) ) {
                                $Customer->setZip01(Str::trimAll($row[$key]));
                            } else {
                                $this->addErrors(($data->key() + 1) . "行目の{$key}は数字を設定してください。");
                                return $this->render($app, $form, $headers, $this->customerTwig);
                            }
                        }
                        $key= '郵便番号２';
                        if (Str::isBlank($row[$key])) {
                            $this->addErrors(($data->key() + 1) . "行目の{$key}が設定されていません。");
                            return $this->render($app, $form, $headers, $this->customerTwig);
                        } else {
                            $zip = str_replace(',', '', $row[$key]);
                            if (preg_match('/^\d+$/', $zip) ) {
                                $Customer->setZip02(Str::trimAll($row[$key]));
                            } else {
                                $this->addErrors(($data->key() + 1) . "行目の{$key}は数字を設定してください。");
                                return $this->render($app, $form, $headers, $this->customerTwig);
                            }
                        }
                        $Customer->setZipcode(Str::trimAll($row['郵便番号１']).Str::trimAll($row['郵便番号２']));


                        $key= '都道府県';
                        if (Str::isBlank($row[$key])) {
                            $this->addErrors(($data->key() + 1) . "行目の{$key}が設定されていません。");
                            return $this->render($app, $form, $headers, $this->customerTwig);
                        } else {
                            $prefstr = str_replace(',', '', $row[$key]);
                            $Pref = $app['eccube.repository.master.pref']->findOneBy(array('name'=>$row[$key]));
                            if ($Pref) {
                                $Customer->setPref($Pref);
                            } else {
                                $this->addErrors(($data->key() + 1) . "行目の{$key}は存在する都道府県名を設定してください。");
                                return $this->render($app, $form, $headers, $this->customerTwig);
                            }
                        }

                        $key= '住所１';
                        if (Str::isBlank($row[$key])) {
                            $this->addErrors(($data->key() + 1) . "行目の{$key}が設定されていません。");
                            return $this->render($app, $form, $headers, $this->customerTwig);
                        } else {
                            $Customer->setAddr01(Str::trimAll($row[$key]));
                        }
                        $key= '住所２';
                        if (Str::isBlank($row[$key])) {
                            $this->addErrors(($data->key() + 1) . "行目の{$key}が設定されていません。");
                            return $this->render($app, $form, $headers, $this->customerTwig);
                        } else {
                            $Customer->setAddr02(Str::trimAll($row[$key]));
                        }
                        $key= 'メール';
                        if (Str::isBlank($row[$key])) {
                            $this->addErrors(($data->key() + 1) . "行目の{$key}が設定されていません。");
                            return $this->render($app, $form, $headers, $this->customerTwig);
                        } else {
                            $Customer->setEmail(Str::trimAll($row[$key]));
                        }

                        $keys= array('電話１','電話２','電話３');
                        foreach($keys as $key){
                            if (Str::isBlank($row[$key])) {
                                $this->addErrors(($data->key() + 1) . "行目の{$key}が設定されていません。");
                                return $this->render($app, $form, $headers, $this->customerTwig);
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
                                    return $this->render($app, $form, $headers, $this->customerTwig);
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
                                    return $this->render($app, $form, $headers, $this->customerTwig);
                                }
                            }
                        }
                        $key= 'メール';
                        if (Str::isBlank($row[$key])) {
                            $this->addErrors(($data->key() + 1) . "行目の{$key}が設定されていません。");
                            return $this->render($app, $form, $headers, $this->customerTwig);
                        } else {
                            if($Customer->getId()==null){
                                $Customermail = $app['orm.em']
                                    ->getRepository('Eccube\Entity\Customer')
                                    ->findOneBy(array('email'=>Str::trimAll($row[$key]),'del_flg'=>0));
                                if ($Customermail) {
                                    $this->addErrors(($data->key() + 1) . '行目のメールアドレスで会員情報が登録済です。');
                                    return $this->render($app, $form, $headers, $this->customerTwig);
                                }


                            }
                            $Customer->setEmail(Str::trimAll($row[$key]));
                        }


                        $key= '性別';
                        if (Str::isBlank($row[$key])) {
                            $this->addErrors(($data->key() + 1) . "行目の{$key}が設定されていません。");
                            return $this->render($app, $form, $headers, $this->customerTwig);
                        } else {
                            $sex = str_replace(',', '', $row[$key]);
                            $Sex = $app['eccube.repository.master.sex']->find($sex);
                            if ($Sex) {
                                $Customer->setSex($Sex);
                            } else {
                                $this->addErrors(($data->key() + 1) . "行目の{$key}は存在する性別IDを設定してください。");
                                return $this->render($app, $form, $headers, $this->customerTwig);
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
                                return $this->render($app, $form, $headers, $this->customerTwig);
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
                                return $this->render($app, $form, $headers, $this->customerTwig);
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
dump($CustomerAddress);
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
                        $key= '会員情報タグ';

                        $ctag = Str::trimAll($row[$key]);
                        if(!$app['eccube.plugin.customertag.service']->createCustomerTagsByCsv($Customer,$ctag)){
                            $this->addErrors(($data->key() + 1) . "行目の{$key}の形式が正しくありません。");
                            return $this->render($app, $form, $headers, $this->customertagTwig);

                        }

                        //メルマガフラグ
                        //

dump($Customer);
dump($CustomerAddress);

                        if ($this->hasErrors()) {
                            return $this->render($app, $form, $headers, $this->customerTwig);
                        }





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










}
