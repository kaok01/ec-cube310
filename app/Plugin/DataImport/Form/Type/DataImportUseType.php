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
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * 購入フロー：ポイント利用画面のFormType
 *
 * @package Plugin\DataImport\Form\Type
 */
class DataImportUseType extends AbstractType
{
    /** @var \Eccube\Application */
    protected $app;

    /**
     * DataImportUseType constructor.
     *
     * @param \Eccube\Application $app
     */
    public function __construct(\Eccube\Application $app)
    {
        $this->app = $app;
    }

    /**
     * Build config type form
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $app = $this->app;

        $builder
            ->add(
                'plg_use_dataimport',
                'text',
                array(
                    'label' => '利用ポイント',
                    'required' => false,
                    'attr' => array(
                        'placeholder' => '使用するポイントを入力 例. 1',
                    ),
                    'constraints' => array(
                        new Assert\LessThanOrEqual(
                            array(
                                'value' => $options['maxUseDataImport'],
                                'message' => '合計金額以内で入力してください。',
                            )
                        ),
                        new Assert\LessThanOrEqual(
                            array(
                                'value' => $options['currentDataImport'],
                                'message' => '保有ポイント以内で入力してください。',
                            )
                        ),
                        new Assert\Length(
                            array(
                                'max' => $app['config']['int_len'],
                            )
                        ),
                        new Assert\Regex(
                            array(
                                'pattern' => "/^\d+$/u",
                                'message' => 'form.type.numeric.invalid',
                            )
                        ),
                    ),
                )
            );
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefault('maxUseDataImport', null);
        $resolver->setDefault('currentDataImport', null);
    }


    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'front_dataimport_use';
    }
}
