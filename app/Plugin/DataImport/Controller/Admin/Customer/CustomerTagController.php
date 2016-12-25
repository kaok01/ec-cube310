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

class CustomerTagController extends \Plugin\DataImport\Controller\Base\CsvImportController
{


    private $customertagTwig = 'DataImport/Resource/template/admin/Customer/csv_customertag.twig';



    /**
     * 会員登録CSVアップロード
     */
    public function csvCustomerTag(Application $app, Request $request)
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
                            return $this->render($app, $form, $headers, $this->customertagTwig);
                        }

                        $id = $row['会員ID'];
dump('a');
                        if ($id == '') {
                            $this->addErrors(($data->key() + 1) . '行目の会員IDが存在しません。');
                            return $this->render($app, $form, $headers, $this->customertagTwig);

                        } else {
                            if (preg_match('/^\d+$/', $row['会員ID'])) {
                                $Customer = $app['orm.em']
                                    ->getRepository('Eccube\Entity\Customer')
                                    ->find($id);
                                if (!$Customer) {
                                    $this->addErrors(($data->key() + 1) . '行目の会員IDが存在しません。');
                                    return $this->render($app, $form, $headers, $this->customertagTwig);
                                }
                               
                            } else {
                                $this->addErrors(($data->key() + 1) . '行目の会員IDが存在しません。');
                                return $this->render($app, $form, $headers, $this->customertagTwig);
                            }

                        }


                        $key= '会員情報タグ';
                        if (Str::isBlank($row[$key])) {
                            $this->addErrors(($data->key() + 1) . "行目の{$key}が設定されていません。");
                            return $this->render($app, $form, $headers, $this->customertagTwig);
                        } else {
                            if(!$app['eccube.plugin.customertag.service']->createCustomerTag($Customer,$row[$key])){
                                $this->addErrors(($data->key() + 1) . "行目の{$key}の形式が正しくありません。");
                                return $this->render($app, $form, $headers, $this->customertagTwig);

                            }

                        }



                        if ($this->hasErrors()) {
                            return $this->render($app, $form, $headers, $this->customertagTwig);
                        }





                    }

                    $this->em->flush();
                    $this->em->getConnection()->commit();

                    //log_info('会員CSV登録完了');

                    $app->addSuccess('admin.dataimport.customertag.csv_import.save.complete', 'admin');
                }

            }
        }

        return $this->render($app, $form, $headers, $this->customertagTwig);
    }


    /**
     * 会員情報タグCSVの出力.
     *
     * @param Application $app
     * @param Request $request
     * @return StreamedResponse
     */
    public function export(Application $app, Request $request)
    {
        // タイムアウトを無効にする.
        set_time_limit(0);

        // sql loggerを無効にする.
        $em = $app['orm.em'];
        $em->getConfiguration()->setSQLLogger(null);

        
        $response = new StreamedResponse();

        $headers = $this->getCustomerTagCsvHeader();

        $data = $app['eccube.plugin.customertag.service']->getCustomerTagAll();

        $response->setCallback(function () use ($app, $request, $headers,$data) {
            // ヘッダ行の出力
            $row = array();
            foreach ($headers as $key => $value) {
                $row[] = mb_convert_encoding($key, $app['config']['csv_export_encoding'], 'UTF-8');
            }

            $fp = fopen('php://output', 'w');
            fputcsv($fp, $row, $app['config']['csv_export_separator']);

            foreach($data as $rowdt){
                fputcsv($fp, $rowdt, $app['config']['csv_export_separator']);

            }
            fclose($fp);

        });

        $now = new \DateTime();
        $filename = 'customertag_' . $now->format('YmdHis') . '.csv';

        $response->headers->set('Content-Type', 'application/octet-stream');
        $response->headers->set('Content-Disposition', 'attachment; filename=' . $filename);
        $response->send();

        return $response;

    }









}
