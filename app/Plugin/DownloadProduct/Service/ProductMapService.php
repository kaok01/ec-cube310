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

class ProductMapService
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

	/**
	 * おすすめ商品情報を新規登録する
	 * @param $data
	 * @return bool
	 */
	public function createProductMap($data) {
		// おすすめ商品詳細情報を生成する
		$Recommend = $this->newProductMap($data);

		$em = $this->app['orm.em'];

		// おすすめ商品情報を登録する
		$em->persist($Recommend);

		$em->flush();

		return true;
	}

	/**
	 * おすすめ商品情報を更新する
	 * @param $data
	 * @return bool
	 */
	public function updateProductMap($data) {
		$dateTime = new \DateTime();
		$em = $this->app['orm.em'];

		// おすすめ商品情報を取得する
		$Recommend =$this->app['eccube.plugin.downloadproduct.repository.productmap_product']->find($data['id']);
		if(is_null($Recommend)) {
			false;
		}

		// おすすめ商品情報を書き換える
		$Recommend->setRefId($data['refid']);
		$Recommend->setProduct($data['Product']);
		$Recommend->setUpdateDate($dateTime);

		// おすすめ商品情報を更新する
		$em->persist($Recommend);

		$em->flush();

		return true;
	}

	/**
	 * おすすめ商品情報を削除する
	 * @param $recommendId
	 * @return bool
	 */
	public function deleteProductMap($recommendId) {
		$currentDateTime = new \DateTime();
		$em = $this->app['orm.em'];

		// おすすめ商品情報を取得する
		$Recommend =$this->app['eccube.plugin.downloadproduct.repository.productmap_product']->find($recommendId);
		if(is_null($Recommend)) {
			false;
		}
		// おすすめ商品情報を書き換える

		// おすすめ商品情報を登録する
		$em->remove($Recommend);

		$em->flush();

		return true;
	}


	/**
	 * おすすめ商品情報を生成する
	 * @param $data
	 * @return \Plugin\Recommend\Entity\RecommendProduct
	 */
	protected function newProductMap($data) {
		$dateTime = new \DateTime();

		//$rank = $this->app['eccube.plugin.downloadproduct.repository.productmap_product']->getMaxRank();

		$Recommend = new \Plugin\DownloadProduct\Entity\ProductMapProduct();
		$Recommend->setRefId($data['refid']);
		$Recommend->setProduct($data['Product']);
		$Recommend->setCreateDate($dateTime);
		$Recommend->setUpdateDate($dateTime);

		return $Recommend;
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
