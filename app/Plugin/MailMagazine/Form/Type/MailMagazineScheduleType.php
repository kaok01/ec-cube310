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
/*
 * [メルマガ配信]-[配信スケジュール設定]用Form
 */

namespace Plugin\MailMagazine\Form\Type;

use Symfony\Component\Form\AbstractType;
use \Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormError;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\Form\CallbackTransformer;

class MailMagazineScheduleType extends AbstractType
{
    private $app;

    public function __construct($app)
    {
        $this->app = $app;
    }

    /**
    * {@inheritdoc}
    */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $config = $this->app['config'];
        $builder
            ->add('schedule_name', 'text', array(
                'label' => '配信スケジュール名',
                'required' => true,
                'constraints' => array(
                    new Assert\NotBlank(),
                    new Assert\Length(array('max' => $config['stext_len'])),
                ),
            ))        
            ->add('send_week', 'choice', array(
                'label' => '配信間隔',
                'required' => true,
                'choices' => array('日','月','火','水','木','金','土'),
                'expanded' => true,
                'multiple' => true
            ))        
            ->add('send_time', 'time', array(
                'label' => '配信時間',
                'required' => true,
                'input' => 'datetime',
                'widget' => 'single_text',
                //'format' => 'H:i',
                //'empty_value' => array('year' => '----', 'month' => '--', 'day' => '--'),
            ))
            ->add('send_start', 'birthday', array(
                'label' => '配信開始日',
                'required' => true,
                'input' => 'datetime',
                'widget' => 'single_text',
                'format' => 'yyyy-MM-dd',
                'empty_value' => array('year' => '----', 'month' => '--', 'day' => '--'),
            ))
            ->add('send_end', 'birthday', array(
                'label' => '配信終了日',
                'required' => true,
                'input' => 'datetime',
                'widget' => 'single_text',
                'format' => 'yyyy-MM-dd',
                'empty_value' => array('year' => '----', 'month' => '--', 'day' => '--'),
            ))
            ->add('sendrepeat_flg', 'checkbox', array(
                'label' => '繰返し送信',
                'required' => false,
                'trim' => true,
                'value' => 0,
                'constraints' => array(
                    new Assert\NotBlank(),
                ),
            ))        
            ->add('enable_flg', 'checkbox', array(
                'label' => '有効・無効',
                'required' => false,
                'trim' => true,
                'value' => 1,
                'constraints' => array(
                    new Assert\NotBlank(),
                ),
            ))
            ->add('id', 'hidden')
            ->addEventSubscriber(new \Eccube\Event\FormEventSubscriber());
        ;

        $builder->get('sendrepeat_flg')
            ->addModelTransformer(new CallbackTransformer(
                function ($outval) {
                    // transform the string back to an array
                    return $outval?true:false;
                },
                function ($inval) {
                    // transform the array to a string
                    return $inval?1:0;
                }
            ))
        ;        

        $builder->get('send_week')
            ->addModelTransformer(new CallbackTransformer(
                function ($outval) {
                    // transform the string back to an array
                    return $outval?
                        (
                            is_array($outval)?$outval:unserialize(base64_decode($outval))
                            ):null;
                }
                ,
                function ($inval) {
                    // transform the array to a string
                    return $inval;
                }
                
            ))
        ;        

        $builder->get('enable_flg')
            ->addModelTransformer(new CallbackTransformer(
                function ($outval) {
                    // transform the string back to an array
                    return $outval?true:false;
                },
                function ($inval) {
                    // transform the array to a string
                    return $inval?1:0;
                }
            ))
        ;        
    }

    /**
    * {@inheritdoc}
    */
    public function getName()
    {
        return 'mail_magazine_schedule';
    }
}
