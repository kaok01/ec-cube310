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


namespace Plugin\DataImport\Controller\Base;

use Eccube\Application;
use Eccube\Common\Constant;
use Eccube\Exception\CsvImportException;
use Eccube\Service\CsvImportService;
use Eccube\Util\Str;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class CsvImportController
{

    protected $errors = array();

    protected $fileName;

    protected $em;





    /**
     * アップロード用CSV雛形ファイルダウンロード
     */
    public function csvTemplate(Application $app, Request $request, $type)
    {
        set_time_limit(0);

        $response = new StreamedResponse();

        if ($type == 'customer') {
            $headers = $this->getCustomerCsvHeader();
            $filename = 'customer.csv';
        } else if ($type == 'order') {
            $headers = $this->getOrderCsvHeader();
            $filename = 'order.csv';
        } else {
            throw new NotFoundHttpException();
        }

        $response->setCallback(function () use ($app, $request, $headers) {

            // ヘッダ行の出力
            $row = array();
            foreach ($headers as $key => $value) {
                $row[] = mb_convert_encoding($key, $app['config']['csv_export_encoding'], 'UTF-8');
            }

            $fp = fopen('php://output', 'w');
            fputcsv($fp, $row, $app['config']['csv_export_separator']);
            fclose($fp);

        });

        $response->headers->set('Content-Type', 'application/octet-stream');
        $response->headers->set('Content-Disposition', 'attachment; filename=' . $filename);
        $response->send();

        return $response;
    }


    /**
     * 登録、更新時のエラー画面表示
     *
     */
    protected function render($app, $form, $headers, $twig)
    {

        if ($this->hasErrors()) {
            if ($this->em) {
                $this->em->getConnection()->rollback();
            }
        }

        if (!empty($this->fileName)) {
            try {
                $fs = new Filesystem();
                $fs->remove($app['config']['csv_temp_realdir'] . '/' . $this->fileName);
            } catch (\Exception $e) {
                // エラーが発生しても無視する
            }
        }

        return $app->render($twig, array(
            'form' => $form->createView(),
            'headers' => $headers,
            'errors' => $this->errors,
        ));
    }


    /**
     * アップロードされたCSVファイルの行ごとの処理
     *
     * @param $formFile
     * @return CsvImportService
     */
    protected function getImportData($app, $formFile)
    {
        // アップロードされたCSVファイルを一時ディレクトリに保存
        $this->fileName = 'upload_' . Str::random() . '.' . $formFile->getClientOriginalExtension();
        $formFile->move($app['config']['csv_temp_realdir'], $this->fileName);

        $file = file_get_contents($app['config']['csv_temp_realdir'] . '/' . $this->fileName);

        if ('\\' === DIRECTORY_SEPARATOR && PHP_VERSION_ID >= 70000) {
            // Windows 環境の PHP7 の場合はファイルエンコーディングを CP932 に合わせる
            // see https://github.com/EC-CUBE/ec-cube/issues/1780
            setlocale(LC_ALL, ''); // 既定のロケールに設定
            if (mb_detect_encoding($file) === 'UTF-8') { // UTF-8 を検出したら SJIS-win に変換
                $file = mb_convert_encoding($file, 'SJIS-win', 'UTF-8');
            }
        } else {
            // アップロードされたファイルがUTF-8以外は文字コード変換を行う
            $encode = Str::characterEncoding(substr($file, 0, 6));
            if ($encode != 'UTF-8') {
                $file = mb_convert_encoding($file, 'UTF-8', $encode);
            }
        }
        $file = Str::convertLineFeed($file);

        $tmp = tmpfile();
        fwrite($tmp, $file);
        rewind($tmp);
        $meta = stream_get_meta_data($tmp);
        $file = new \SplFileObject($meta['uri']);

        set_time_limit(0);

        // アップロードされたCSVファイルを行ごとに取得
        $data = new CsvImportService($file, $app['config']['csv_import_delimiter'], $app['config']['csv_import_enclosure']);

        $ret = $data->setHeaderRowNumber(0);

        return ($ret !== false) ? $data : false;
    }








    /**
     * 登録、更新時のエラー画面表示
     *
     */
    protected function addErrors($message)
    {
        $e = new CsvImportException($message);
        $this->errors[] = $e;
    }

    /**
     * @return array
     */
    protected function getErrors()
    {
        return $this->errors;
    }

    /**
     *
     * @return boolean
     */
    protected function hasErrors()
    {
        return count($this->getErrors()) > 0;
    }

    /**
     * 会員登録CSVヘッダー定義
     */
    private function getCustomerCsvHeader()
    {
        return array(
            '商品ID' => 'id',
            '公開ステータス(ID)' => 'status',
            '商品名' => 'name',
            'ショップ用メモ欄' => 'note',
            '商品説明(一覧)' => 'description_list',
            '商品説明(詳細)' => 'description_detail',
            '検索ワード' => 'search_word',
            'フリーエリア' => 'free_area',
            '商品削除フラグ' => 'product_del_flg',
            '商品画像' => 'product_image',
            '商品カテゴリ(ID)' => 'product_category',
            'タグ(ID)' => 'product_tag',
            '商品種別(ID)' => 'product_type',
            '規格分類1(ID)' => 'class_category1',
            '規格分類2(ID)' => 'class_category2',
            '発送日目安(ID)' => 'deliveryFee',
            '商品コード' => 'product_code',
            '在庫数' => 'stock',
            '在庫数無制限フラグ' => 'stock_unlimited',
            '販売制限数' => 'sale_limit',
            '通常価格' => 'price01',
            '販売価格' => 'price02',
            '送料' => 'delivery_fee',
            '商品規格削除フラグ' => 'product_class_del_flg',
        );
    }

    /**
     * 受注登録CSVヘッダー定義
     */
    private function getOrderCsvHeader()
    {
        return array(
            '商品ID' => 'id',
            '公開ステータス(ID)' => 'status',
            '商品名' => 'name',
            'ショップ用メモ欄' => 'note',
            '商品説明(一覧)' => 'description_list',
            '商品説明(詳細)' => 'description_detail',
            '検索ワード' => 'search_word',
            'フリーエリア' => 'free_area',
            '商品削除フラグ' => 'product_del_flg',
            '商品画像' => 'product_image',
            '商品カテゴリ(ID)' => 'product_category',
            'タグ(ID)' => 'product_tag',
            '商品種別(ID)' => 'product_type',
            '規格分類1(ID)' => 'class_category1',
            '規格分類2(ID)' => 'class_category2',
            '発送日目安(ID)' => 'deliveryFee',
            '商品コード' => 'product_code',
            '在庫数' => 'stock',
            '在庫数無制限フラグ' => 'stock_unlimited',
            '販売制限数' => 'sale_limit',
            '通常価格' => 'price01',
            '販売価格' => 'price02',
            '送料' => 'delivery_fee',
            '商品規格削除フラグ' => 'product_class_del_flg',
        );
    }

}
