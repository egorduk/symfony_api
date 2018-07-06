<?php

namespace Btc\FrontendApiBundle\Form;

use Btc\CoreBundle\Entity\Country;
use Btc\CoreBundle\Entity\UserBusinessInfo;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class BusinessInfoType extends AbstractType
{
    const COUNT_FILES = 4;
    const START_INDEX_FILES = 1;

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('companyName', 'text')
            ->add('vatId', 'text')
            ->add('registrationNumber', 'text')
            ->add('country', 'entity', [
                'class' => Country::class,
                'property' => 'name',
            ])
            ->add('state', 'text')
            ->add('city', 'text')
            ->add('street', 'text')
            ->add('building', 'text')
            ->add('zipCode', 'text')
            ->add('officeNumber', 'text', ['required' => false]);

        for ($i = self::START_INDEX_FILES; $i <= self::COUNT_FILES; ++$i) {
            $builder->add('companyDetails'.$i.'Content', 'text', ['required' => false]);
            $builder->add('companyDetails'.$i.'Name', 'text', ['required' => false]);
        }
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => UserBusinessInfo::class,
        ]);
    }

    public function getName()
    {
        return '';
    }
}
