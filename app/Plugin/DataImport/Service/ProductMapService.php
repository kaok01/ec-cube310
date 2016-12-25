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

namespace Plugin\DataImport\Service;

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
		$Recommend =$this->app['eccube.plugin.dataimport.repository.productmap_product']->find($data['id']);
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
		$Recommend =$this->app['eccube.plugin.dataimport.repository.productmap_product']->find($recommendId);
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

		$rank = $this->app['eccube.plugin.dataimport.repository.productmap_product']->getMaxRank();

		$Recommend = new \Plugin\DataImport\Entity\ProductMapProduct();
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

    // テンプレートに商品の情報を渡す
	public function getProductParam($product_list){
		$app = $this->app;
		$value_repository = $app['orm.em']->getRepository('\Plugin\PlgExpandProductColumns\Entity\PlgExpandProductColumnsValue');
		$column_repository = $app['orm.em']->getRepository('\Plugin\PlgExpandProductColumns\Entity\PlgExpandProductColumns');
		$maker_repository = $app['eccube.plugin.maker.repository.product_maker'];

		$__ex_product_list = array();
		$__ex_product_list_maker = array();
		foreach ($product_list as $Product) {
			$__ex_product_list[$Product['Product']->getId()] = $this->getProductExt($Product['Product']->getId(), $value_repository, $column_repository);
			if(!is_null($maker_repository->find($Product['Product']->getId()))){
				$__ex_product_list_maker[$Product['Product']->getId()]['name'] = $maker_repository->find($Product['Product']->getId())->getMaker()->getName();
				$__ex_product_list_maker[$Product['Product']->getId()]['url'] = $maker_repository->find($Product['Product']->getId())->getMakerUrl();
			}
		}

		return array(
			'__EX_PRODUCT_LIST' => $__ex_product_list,
			'__EX_PRODUCT_LIST_MAKER' => $__ex_product_list_maker,
			);
	}


	// Plugin\PlgExpandProductColumns\Event.php からコピペ
	private function getProductExt($id, $value_repository, $column_repository)
	{
		$product_ex = array();
		$columns = $column_repository->findAll();

		/** @var \Plugin\PlgExpandProductColumns\Entity\PlgExpandProductColumns $column */
		foreach ($columns as $column) {
			$value = $value_repository->findOneBy(array(
				'columnId' => $column->getColumnId(),
				'productId' => $id));
			/**
			 * 配列系の値の場合、配列にしてから渡す
			 */
			switch ($column->getColumnType()) {
				case EX_TYPE_IMAGE :
				case EX_TYPE_CHECKBOX :
					if (empty($value)) {
						$value = '';
					} else {
						$value = explode(',', $value->getValue());
					}
					break;
				default :
					$value = empty($value) ? '' : $value->getValue();
			}
			$valuetext = '';
			$valset = explode("\r\n",$column->getColumnSetting());
			//dump($valset);
			$vss = array();
			foreach($valset as $vs){
				if(!empty($vs)){

					$vs =  explode(':',$vs);
					if(isset($vs[0])){
					$vss[$vs[0]] = $vs[1];
					}
				}
			}
			//dump($vss);
			

			switch ($column->getColumnType()) {
				case EX_TYPE_CHECKBOX :
					if (empty($value)) {
						$valuetext = '';
					} else {
						foreach($value as $v){
							$valuetext[] = $vss[$v];
						}
					}
					break;

				case EX_TYPE_SELECT :
				case EX_TYPE_RADIO :
					if (empty($value)) {
						$valuetext = '';
					} else {
						$valuetext = $vss[$value];
					}
					break;
				default :
					$valuetext = $value;
			}

			$product_st[$column->getColumnName()] = array(
				'id' => $column->getColumnId(),
				'name' => $column->getColumnName(),
				'value' => $value
				,'valuetext'=> $valuetext
			);

			$product_ex[$column->getColumnId()] = array(
				'id' => $column->getColumnId(),
				'name' => $column->getColumnName(),
				'value' => $value
				,'valuetext'=> $valuetext
			);
		}
		ksort($product_st);
		$product_ex=array();
		foreach($product_st as $ex){
			$product_ex[$ex['id']] = $ex;
		}


		return $product_ex;
	}


}
