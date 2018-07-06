<?php

namespace Btc\FrontendApiBundle\Form;

use Btc\CoreBundle\Entity\Verification;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UserVerificationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('personalInfo', new PersonalInfoType(), ['required' => false])
            ->add('businessInfo', new BusinessInfoType(), ['required' => false]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Verification::class,
            'csrf_protection' => false,
            'allow_extra_fields' => true,
            'validation_groups' => ['api'],
        ]);
    }

    public function getName()
    {
        return '';
    }
}
