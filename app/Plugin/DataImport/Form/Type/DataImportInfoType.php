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
namespace Plugin\DataImport\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Class DataImportInfoType
 * @package Plugin\DataImport\Form\Type
 */
class DataImportInfoType extends AbstractType
{
    /** @var \Eccube\Application */
    protected $app;
    /** @var array */
    protected $orderStatus;

    /**
     * DataImportInfoType constructor.
     * @param \Eccube\Application $app
     */
    public function __construct(\Eccube\Application $app)
    {
        $this->app = $app;
        // 全受注ステータス ID・名称 取得保持
        $this->orderStatus = array();
        $this->app['orm.em']->getFilters()->enable('incomplete_order_status_hidden');
        foreach ($this->app['eccube.repository.order_status']->findAllArray() as $id => $node) {
            $this->orderStatus[$id] = $node['name'];
        }
        $this->app['orm.em']->getFilters()->disable('incomplete_order_status_hidden');
    }

    /**
     * Build config type form
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'plg_add_dataimport_status',
                'choice',
                array(
                    'label' => 'データインポート確定タイミング',
                    'choices' => $this->orderStatus,
                    'mapped' => true,
                    'expanded' => false,
                    'multiple' => false,
                    'constraints' => array(
                        new Assert\NotBlank(),
                    ),
                )
            )
            ->add(
                'plg_calculation_type',
                'choice',
                array(
                    'label' => 'データインポート減算方式',
                    'choices' => array(
                        \Plugin\DataImport\Entity\DataImportInfo::POINT_CALCULATE_SUBTRACTION => 'データインポート利用時に減算',
                        \Plugin\DataImport\Entity\DataImportInfo::POINT_CALCULATE_NORMAL => '減算なし',
                    ),
                    'mapped' => true,
                    'expanded' => false,
                    'multiple' => false,
                    'constraints' => array(
                        new Assert\NotBlank(),
                    ),
                )
            )
            ->add(
                'plg_basic_dataimport_rate',
                'integer',
                array(
                    'label' => '基本データインポート付与率',
                    'required' => true,
                    'mapped' => true,
                    'empty_data' => null,
                    'attr' => array(
                        'placeholder' => '「商品毎の付与率」が設定されていない場合に本値が適用されます。( ％ )',
                    ),
                    'constraints' => array(
                        new Assert\NotBlank(),
                        new Assert\Range(
                            array(
                                'min' => 1,
                                'max' => 100,
                            )
                        ),
                    ),
                )
            )
            ->add(
                'plg_dataimport_conversion_rate',
                'integer',
                array(
                    'label' => 'データインポート換算レート',
                    'required' => true,
                    'mapped' => true,
                    'empty_data' => null,
                    'attr' => array(
                        'placeholder' => 'データインポート利用時の換算値です( 1 → 1pt = 1円 )',
                    ),
                    'constraints' => array(
                        new Assert\NotBlank(),
                        new Assert\Range(
                            array(
                                'min' => 1,
                                'max' => 100,
                            )
                        ),
                    ),
                )
            )
            ->add(
                'plg_round_type',
                'choice',
                array(
                    'label' => 'データインポート端数計算方法',
                    'choices' => array(
                        \Plugin\DataImport\Entity\DataImportInfo::POINT_ROUND_CEIL => '切り上げ',
                        \Plugin\DataImport\Entity\DataImportInfo::POINT_ROUND_FLOOR => '切り捨て',
                        \Plugin\DataImport\Entity\DataImportInfo::POINT_ROUND_ROUND => '四捨五入',
                    ),
                    'mapped' => true,
                    'expanded' => false,
                    'multiple' => false,
                    'constraints' => array(
                        new Assert\NotBlank(),
                    ),
                )
            );
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            array(
                'data_class' => 'Plugin\DataImport\Entity\DataImportInfo',
            )
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'admin_dataimport_info';
    }
}
