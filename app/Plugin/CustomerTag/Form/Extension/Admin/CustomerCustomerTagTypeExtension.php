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

namespace Plugin\CustomerTag\Form\Extension\Admin;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;
use Doctrine\ORM\EntityRepository;

class CustomerCustomerTagTypeExtension extends AbstractTypeExtension
{
    private $app;

    public function __construct(\Eccube\Application $app)
    {
        $this->app = $app;
    }


    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('customertag', 'entity', array(
                'label' => '会員情報タグ',
                'class' => 'Plugin\CustomerTag\Entity\CustomerTag',
                'property' => 'name',
                'required' => false,
                'empty_value' => '',
                'mapped' => false,
            ))
            ->add('customertag_url', 'text', array(
                'label' => 'URL',
                'required' => false,
                'constraints' => array(
                    new Assert\Url(),
                ),
                'mapped' => false,
            ))
            ;
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
    }


    public function getExtendedType()
    {
        return 'admin_customer';
    }
}