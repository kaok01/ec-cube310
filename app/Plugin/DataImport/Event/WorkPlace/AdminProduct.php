<?php
/*
* This file is part of EC-CUBE
*
* Copyright(c) 2000-2016 LOCKON CO.,LTD. All Rights Reserved.
* http://www.lockon.co.jp/
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace Plugin\DataImport\Event\WorkPlace;

use Eccube\Event\EventArgs;
use Symfony\Component\Form\FormBuilder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * フックデータインポート汎用処理具象クラス
 *  - 拡張元 : 商品登録( 編集 )
 *  - 拡張項目 : 商品毎データインポート付与率( 編集 )
 * Class AdminProduct
 * @package Plugin\DataImport\Event\WorkPlace
 */
class  AdminProduct extends AbstractWorkPlace
{
    /**
     * 商品フォームデータインポート付与率項目追加
     *
     * @param EventArgs $event
     */
    public function createForm(EventArgs $event)
    {
        $builder = $event->getArgument('builder');
        $Product = $event->getArgument('Product');

        // 登録済み情報取得処理
        $lastDataImportProduct = null;
        if (!is_null($Product->getId())) {
            $lastDataImportProduct = $this->app['eccube.plugin.dataimport.repository.dataimportproductrate']->getLastDataImportProductRateById(
                $Product->getId()
            );
        }

        // データインポート付与率項目拡張
        $builder
            ->add(
                'plg_dataimport_product_rate',
                'integer',
                array(
                    'label' => 'データインポート付与率',
                    'required' => false,
                    'mapped' => false,
                    'data' => $lastDataImportProduct,
                    'constraints' => array(
                        new Assert\Regex(
                            array(
                                'pattern' => "/^\d+$/u",
                                'message' => 'form.type.numeric.invalid',
                            )
                        ),
                        new Assert\Range(
                            array(
                                'min' => 0,
                                'max' => 100,
                            )
                        ),
                    ),
                )
            );
    }

    /**
     * 商品毎データインポート付与率保存
     * @param EventArgs $event
     * @return bool
     */
    public function save(EventArgs $event)
    {
        $this->app['monolog.dataimport.admin']->addInfo('save start');

        // フォーム情報取得処理
        $form = $event->getArgument('form');

        // データインポート付与率取得
        $dataimportRate = $form->get('plg_dataimport_product_rate')->getData();

        $Product = $event->getArgument('Product');

        // 前回入力値と比較
        $status = $this->app['eccube.plugin.dataimport.repository.dataimportproductrate']
            ->isSameDataImport($dataimportRate, $Product->getId());

        $this->app['monolog.dataimport.admin']->addInfo('save add product dataimport', array(
                'product_id' => $Product->getId(),
                'status' => $status,
                'add dataimport' => $dataimportRate,
            )
        );

        // 前回入力値と同じ値であれば登録をキャンセル
        if ($status) {
            return true;
        }

        // データインポート付与保存処理
        $this->app['eccube.plugin.dataimport.repository.dataimportproductrate']->saveDataImportProductRate($dataimportRate, $Product);

        $this->app['monolog.dataimport.admin']->addInfo('save end');
    }
}
