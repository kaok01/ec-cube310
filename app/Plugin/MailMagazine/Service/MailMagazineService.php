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

namespace Plugin\MailMagazine\Service;

use Eccube\Application;
use Eccube\Common\Constant;

class MailMagazineService
{
    // ====================================
    // 定数宣言
    // ====================================
    const REPOSITORY_SEND_HISTORY = 'eccube.plugin.mail_magazine.repository.mail_magazine_send_history';
    const REPOSITORY_SEND_CUSTOMER = 'eccube.plugin.mail_magazine.repository.mail_magazine_send_customer';

    const REPOSITORY_SEND_SCHEDULE = 'eccube.plugin.mail_magazine.repository.mail_magazine_send_schedule';

    // send_flagの定数
    /** メール送信成功 */
    const SEND_FLAG_SUCCESS = 1;
    /** メール送信失敗 */
    const SEND_FLAG_FAILURE = 2;

    // ====================================
    // 変数宣言
    // ====================================
    /** @var \Eccube\Application */
    public $app;

    /**
     * 最後に送信者に送信したメールの本文.
     * @var string
     */
    private $lastSendMailBody = "";

    /** @var \Eccube\Entity\BaseInfo */
    public $BaseInfo;


    public function __construct(Application $app)
    {
        $this->app = $app;
        $this->BaseInfo = $app['eccube.repository.base_info']->get();
    }

    /**
     * メールを送信する
     * @param array $formData メルマガ情報
     *                  email: 送信先メールアドレス
     *                  subject: 件名
     *                  body：本文
     */
    protected function sendMail($formData) {
        // メール送信
        $message = \Swift_Message::newInstance()
            ->setSubject($formData['subject'])
            ->setFrom(array($this->BaseInfo->getEmail01() => $this->BaseInfo->getShopName()))
            ->setTo(array($formData['email']))
            //             ->setBcc($this->BaseInfo->getEmail01())
            ->setReplyTo($this->BaseInfo->getEmail03())
            ->setReturnPath($this->BaseInfo->getEmail04())
            ->setBody($formData['body']);

        return $this->app->mail($message);
    }

    public function createMailMagazineHistoryNoSend($formData){
        return $this->createMailMagazineHistory($formData,true);

    }

    /**
     * 配信履歴を作成する
     *
     * @param unknown $formData
     * @return 採番されたsend_id
     *         エラー時はfalseを返す
     * @throws Exception
     */
    public function createMailMagazineHistory($formData,$nosend = false) {

        // メール配信先リストの取得
        $this->app['eccube.plugin.mail_magazine.repository.mail_magazine_customer']->setApplication($this->app);
        if($nosend){
            $customerList = array();

        }else{
            $customerList = $this->app['eccube.plugin.mail_magazine.repository.mail_magazine_customer']
                ->getCustomerBySearchData($formData);


        }
        $currentDatetime = new \DateTime();

        // -----------------------------
        // dtb_send_historyを登録する
        // -----------------------------
        $sendHistory = new \Plugin\MailMagazine\Entity\MailMagazineSendHistory();

        // 登録値を設定する
        $sendHistory->setBody($formData['body']);
        $sendHistory->setSubject($formData['subject']);
        $sendHistory->setSendCount(count($customerList));
        $sendHistory->setCompleteCount(0);
        $sendHistory->setDelFlg(Constant::DISABLED);

        $sendHistory->setEndDate(null);
        $sendHistory->setUpdateDate(null);

        $sendHistory->setCreateDate($currentDatetime);
        $sendHistory->setStartDate($currentDatetime);

        // Formから検索条件を取得し、シリアライズする(array)
        // 事前に不要な項目は削除する
        unset($formData['pageno']);
        unset($formData['pagemax']);
        unset($formData['id']);
        unset($formData['subject']);
        unset($formData['body']);

        // serializeのみだとDB登録時にデータが欠損するのでBase64にする
        $sendHistory->setSearchData(base64_encode(serialize($formData)));

        $status = $this->app[self::REPOSITORY_SEND_HISTORY]->createSendHistory($sendHistory);
        if(!$status) {
            return null;
        }

        // -----------------------------
        // dtb_send_customerを登録する
        // -----------------------------
        $sendId = $sendHistory->getId();
        foreach($customerList as $customer) {
            // Entityにデータを設定する
            $sendCustomer = new \Plugin\MailMagazine\Entity\MailMagazineSendCustomer();

            $sendCustomer->setSendId($sendId);
            $sendCustomer->setCustomerId($customer->getId());
            $sendCustomer->setEmail($customer->getEmail());
            $sendCustomer->setName($customer->getName01() . " " . $customer->getName02());

            $status = $this->app[self::REPOSITORY_SEND_CUSTOMER]->createSendCustomer($sendCustomer);
        }

        return $sendId;
    }

    /**
     * Send mailmagazine.
     * メールマガジンを送信する.
     *
     * @param unknown $sendId
     */
    public function sendrMailMagazine($sendId)
    {
        // 最後に送信したメール本文をクリアする
        $this->lastSendMailBody = "";

        // send_historyを取得する
        $sendHistory = $this->app[self::REPOSITORY_SEND_HISTORY]->find($sendId);

        if(is_null($sendHistory)) {
            // 削除されている場合は終了する
            return false;
        }
        // send_customerを取得する
        $sendCustomerList = $this->app[self::REPOSITORY_SEND_CUSTOMER]->getSendCustomerByNotSuccess($sendId);

        // 配信済数を取得する
        $compleateCount = $sendHistory->getCompleteCount();

        // 取得したメルマガ配信者分メールを送信する
        foreach ($sendCustomerList as $sendCustomer) {
            // メール送信
            $name = trim($sendCustomer->getName());
            $body = preg_replace('/{name}/', $name, $sendHistory->getBody());
            // 送信した本文を保持する
            $this->lastSendMailBody = $body;
            $mailData = array(
                    'email' => $sendCustomer->getEmail(),
                    'subject' => preg_replace('/{name}/', $name, $sendHistory->getSubject()),
                    'body' => $body
            );
            try {
                $sendResult = $this->sendMail($mailData);
            } catch(\Exception $e) {
                $sendResult = false;
            }

            if(!$sendResult) {
                // メール送信失敗時
                $sendFlag = self::SEND_FLAG_FAILURE;
            } else {
                // メール送信成功時
                $sendFlag = self::SEND_FLAG_SUCCESS;
                $compleateCount++;
            }

            // 履歴更新
            $sendCustomer->setSendFlag($sendFlag);
            try {
                $this->app[self::REPOSITORY_SEND_CUSTOMER]->updateSendCustomer($sendCustomer);
            }catch(\Exception $e) {
                throw $e;
            }
        }

        // 送信結果情報を更新する
        $sendHistory->setEndDate(new \DateTime());
        $sendHistory->setCompleteCount($compleateCount);
        $this->app[self::REPOSITORY_SEND_HISTORY]->updateSendHistory($sendHistory);

        return true;
    }

    /**
     * Send mailmagazine.
     * メールマガジンを送信する.
     *
     * @param unknown $sendId
     */
    public function createReservedsendMailMagazine($sendId,$scheduledata)
    {
dump($scheduledata);
        // send_historyを取得する
        $sendHistory = $this->app[self::REPOSITORY_SEND_HISTORY]->find($sendId);

        if(is_null($sendHistory)) {
            // 削除されている場合は終了する
            return false;
        }
        $SendSchedule = new \Plugin\MailMagazine\Entity\MailMagazineSendSchedule();

        $currentDatetime = new \DateTime();

        // 送信結果情報を更新する
        $SendSchedule
            ->setScheduleName($scheduledata['schedule_name'])
            ->setSendTime($scheduledata['send_time'])
            ->setSendStart($scheduledata['send_start'])
            ->setSendEnd($scheduledata['send_end'])
            ->setScheduleName($scheduledata['schedule_name'])
            ->setSendHistory($sendHistory)
            ->setSendRepeatFlg($scheduledata['sendrepeat_flg'])
            ->setDelFlg(0)
            ->setEnableFlg($scheduledata['enable_flg'])
            ->setCreateDate($currentDatetime)
            ->setUpdateDate($currentDatetime)
        ;

        $status = $this->app[self::REPOSITORY_SEND_SCHEDULE]->createSendSchedule($SendSchedule);
        if(!$status) {
            return null;
        }
        return true;
    }

    /**
     * 送信完了報告メールを送信する
     *
     * @return number
     */
    public function sendMailMagazineCompleateReportMail() {

        $subject = date('Y年m月d日H時i分') . '　下記メールの配信が完了しました。';

        $mailData = array(
                'email' => $this->BaseInfo->getEmail03(),
                'subject' => $subject,
                'body' => $this->lastSendMailBody
        );

        return $this->sendMail($mailData);;
    }


    /**
     * メール送付情報を保存する
     * @param unknown $customerId
     * @param unknown $mailmagaFlg
     */
    public function saveMailmagaCustomer($customerId, $mailmagaFlg)
    {
        // メルマガ送付情報を取得する
        $MailmagaCustomerRepository = $this->app['eccube.plugin.mail_magazine.repository.mail_magazine_mailmaga_customer'];
        $MailmagaCustomer = $MailmagaCustomerRepository->findOneBy(array('customer_id' => $customerId));

        // メルマガ送付情報がない場合は新規に作成する
        if (is_null($MailmagaCustomer)) {
            $MailmagaCustomer = new \Plugin\MailMagazine\Entity\MailmagaCustomer();
            $MailmagaCustomer->setCustomerId($customerId);
            $MailmagaCustomer->setDelFlg(Constant::DISABLED);
            $MailmagaCustomer->setCreateDate(new \DateTime());
        }
        $MailmagaCustomer->setMailmagaFlg($mailmagaFlg);
        $MailmagaCustomer->setUpdateDate(new \DateTime());

        $MailmagaCustomerRepository->save($MailmagaCustomer);
    }
}
