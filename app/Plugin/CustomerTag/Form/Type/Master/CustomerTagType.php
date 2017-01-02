<?php


namespace Plugin\CustomerTag\Form\Type\Master;

use Doctrine\ORM\EntityRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CustomerTagType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $options['customertag_options']['required'] = $options['required'];
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'class' => 'Plugin\CustomerTag\Entity\CustomerTag',
            'expanded' => true,
            'empty_value' => false,
        ));
    }

    public function getParent()
    {
        return 'master';
    }

    public function getName()
    {
        return 'customertag';
    }
}
