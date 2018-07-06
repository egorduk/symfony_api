<?php

namespace Btc\FrontendApiBundle\Form;

use Btc\CoreBundle\Entity\Country;
use Btc\CoreBundle\Entity\UserPersonalInfo;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PersonalInfoType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('firstName', 'text')
            ->add('lastName', 'text')
            ->add('address', 'text')
            ->add('zipCode', 'text')
            ->add('city', 'text')
            ->add('country', 'entity', [
                'class' => Country::class,
                'property' => 'name',
            ])
            ->add('birthDate', 'date', [
                'input' => 'datetime',
                'widget' => 'single_text',
                'format' => 'yyyy-MM-dd',
            ])
            ->add('phone', 'text', ['required' => false])
            ->add('idPhotoContent', 'text', ['required' => false])
            ->add('idPhotoName', 'text', ['required' => false])
            ->add('residenceProofContent', 'text', ['required' => false])
            ->add('residenceProofName', 'text', ['required' => false])
            ->add('idBackSideContent', 'text', ['required' => false])
            ->add('idBackSideName', 'text', ['required' => false]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => UserPersonalInfo::class,
        ]);
    }

    public function getName()
    {
        return '';
    }
}
