<?php


namespace Plugin\MailMagazine\Form\Type;

use Doctrine\ORM\EntityRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class MailMagazineProductType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $options['mailmagazineproduct_options']['required'] = $options['required'];
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'class' => 'Eccube\Entity\Product',
            'expanded' => false,
            'multiple' => true,
            'empty_value' => '-',
            'query_builder' => function (EntityRepository $er) {
                return $er->createQueryBuilder('m')
                    ->select('m')
                    ->orderBy('m.id', 'ASC');
            },
        ));
    }

    public function getParent()
    {
        return 'entity';
    }

    public function getName()
    {
        return 'mailmagazine_product';
    }
}
