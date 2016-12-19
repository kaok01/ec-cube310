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
    protected function getCustomerCsvHeader()
    {
        return array(
            '会員ID' => 'id',
            '連携ID' => 'refid',
            '会員ステータス' => 'status',
            '会員名１' => 'name01',
            '会員名2' => 'name02',
            '会員カナ１' => 'kana01',
            '会員カナ2' => 'kana02',
            '会社名' => 'company_name',
            '郵便番号１' => 'zip01',
            '郵便番号２' => 'zip02',
            '都道府県' => 'pref',
            '住所１' => 'addr1',
            '住所２' => 'addr2',
            'メール' => 'email',
            '電話１' => 'tel01',
            '電話２' => 'tel02',
            '電話３' => 'tel03',
            'ＦＡＸ１' => 'fax01',
            'ＦＡＸ２' => 'fax02',
            'ＦＡＸ３' => 'fax03',
            'パスワード' => 'password',
            '性別' => 'sex',
            '生年月日' => 'birth',
            'メルマガ受信' => 'mailmaga_flg',
            'ショップ用メモ欄' => 'note',
            '削除フラグ' => 'del_flg',
            '会員情報タグ０１' => 'tag01',
            '会員情報タグ０２' => 'tag02',
            '会員情報タグ０３' => 'tag03',
            '会員情報タグ０４' => 'tag04',
            '会員情報タグ０５' => 'tag05',
            '会員情報タグ０６' => 'tag06',
            '会員情報タグ０７' => 'tag07',
            '会員情報タグ０８' => 'tag08',
            '会員情報タグ０９' => 'tag09',
            '会員情報タグ１０' => 'tag10'
        );
    }


    /**
     * 受注登録CSVヘッダー定義
     */
    protected function getOrderCsvHeader()
    {
        return array(
            '注文ID' => 'id',
            '注文連携ID' => 'refid',
            '会員ID' => 'id',
            '会員ステータス' => 'status',
            '会員名１' => 'name01',
            '会員名2' => 'name02',
            '会員カナ１' => 'kana01',
            '会員カナ2' => 'kana02',
            '会社名' => 'company_name',
            '郵便番号１' => 'zip01',
            '郵便番号２' => 'zip02',
            '都道府県' => 'pref',
            '住所１' => 'addr1',
            '住所２' => 'addr2',
            'メール' => 'email',
            '電話１' => 'tel01',
            '電話２' => 'tel02',
            '電話３' => 'tel03',
            'ＦＡＸ１' => 'fax01',
            'ＦＡＸ２' => 'fax02',
            'ＦＡＸ３' => 'fax03',
            'パスワード' => 'password',
            '性別' => 'sex',
            '生年月日' => 'birth',
            'メルマガ受信' => 'mailmaga_flg',
            'ショップ用メモ欄' => 'note',
            '削除フラグ' => 'del_flg',
            '会員情報タグ０１' => 'tag01',
            '会員情報タグ０２' => 'tag02',
            '会員情報タグ０３' => 'tag03',
            '会員情報タグ０４' => 'tag04',
            '会員情報タグ０５' => 'tag05',
            '会員情報タグ０６' => 'tag06',
            '会員情報タグ０７' => 'tag07',
            '会員情報タグ０８' => 'tag08',
            '会員情報タグ０９' => 'tag09',
            '会員情報タグ１０' => 'tag10'
        );
    }


    protected function getInfotopOrderCsvHeader()
    {
        return array(
            "販売日"=>"",
            "注文ID"=>"",
            "商品名"=>"",
            "価格"=>"",
            "送料"=>"",
            "アフィリ報酬"=>"",
            "2ティア報酬"=>"",
            "アフィリエイタID"=>"",
            "アフィリエイタ名"=>"",
            "2ティアID"=>"",
            "2ティア名"=>"",
            "商品区分"=>"",
            "デリバリング請求額"=>"",
            "決済種別"=>"",
            "決済手数料"=>"",
            "決済回数"=>"",
            "購入元"=>"",
            "売上残額"=>"",
            "商品ID"=>"",
            "注文状況"=>"",
            "購入者ID"=>"",
            "購入者名"=>"",
            "郵便番号"=>"",
            "電話番号"=>"",
            "住所"=>"",
            "PCメールアドレス"=>"",
            "審査部へのメッセージ"=>"",
            "商品詳細"=>"",
            "販売個数"=>"",
            "配送先氏名"=>"",
            "配送先郵便番号"=>"",
            "配送先住所"=>"",
            "配送先TEL"=>""
        );
    }


}
