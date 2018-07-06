<?php

namespace Btc\FrontendApiBundle\Form;

use Btc\CoreBundle\Entity\UserPreference;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SettingType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('value', 'boolean_type');
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'required' => false,
            'data_class' => UserPreference::class,
        ]);
    }

    public function getName()
    {
        return '';
    }
}
