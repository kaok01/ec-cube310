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

namespace Plugin\DownloadProduct\Service;

use Eccube\Application;
use Eccube\Common\Constant;

class DownloadService
{
	/** @var \Eccube\Application */
	public $app;

	/** @var \Eccube\Entity\BaseInfo */
	public $BaseInfo;

	/**
	 * コンストラクタ
	 * @param Application $app
	 */
	public function __construct(Application $app)
	{
		$this->app = $app;
		$this->BaseInfo = $app['eccube.repository.base_info']->get();
	}
    public function ConfirmEmailCustomerExist($email){
    	$result = $this->app['eccube.repository.customer']->findBy(array('email'=>$email,'del_flg'=>0));
    	if($result){
    		return true;

    	}
    	return false;

    }
    public function SendNonMemberResetMail($email){
        $app=$this->app;
        $request=$app['request'];
        $Customer = $app['eccube.repository.customer']
            ->getActiveCustomerByEmail($email);

        if (!is_null($Customer)) {
            // リセットキーの発行・有効期限の設定
            $Customer
                ->setResetKey($app['eccube.repository.customer']->getUniqueResetKey($app))
                ->setResetExpire(new \DateTime('+' . $app['config']['customer_reset_expire'] .' min'));

            // リセットキーを更新
            $app['orm.em']->persist($Customer);
            $app['orm.em']->flush();

            // $event = new EventArgs(
            //     array(
            //         'form' => $form,
            //         'Customer' => $Customer,
            //     ),
            //     $request
            // );
            // $app['eccube.event.dispatcher']->dispatch(EccubeEvents::FRONT_FORGOT_INDEX_COMPLETE, $event);

            // 完了URLの生成
            $reset_url = $app->url('forgot_reset', array('reset_key' => $Customer->getResetKey()));

            // メール送信
            $app['eccube.service.mail']->sendPasswordResetNotificationMail($Customer, $reset_url);

            // ログ出力
            $app['monolog']->addInfo(
                'send reset password mail to:'  . "{$Customer->getId()} {$Customer->getEmail()} {$request->getClientIp()}"
            );
        }

    }
    public function checkInstallPlugin($code)
    {
        $Plugin = $this->app['eccube.repository.plugin']->findOneBy(array('code' => $code, 'enable' => 1));

        if($Plugin){
            return true;
        }else{
            return false;
        }
    }



}
