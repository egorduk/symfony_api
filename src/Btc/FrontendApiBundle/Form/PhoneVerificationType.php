<?php

namespace Btc\FrontendApiBundle\Form;

use Btc\CoreBundle\Entity\PhoneVerification;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PhoneVerificationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('phone', 'text');
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'validation_groups' => ['api'],
            'data_class' => PhoneVerification::class,
            'csrf_protection' => false,
            'allow_extra_fields' => true,
        ]);
    }

    public function getName()
    {
        return '';
    }
}
