<?php

namespace Btc\TransferBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class AstropayDepositType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('amount', 'money', [
                'currency' => false,
            ])
            ->add('currencyCode', 'choice', [
                'choices' => ['USD' => 'USD'],
                'empty_value' => false,
                'disabled' => true,
            ])
            ->add('country', 'choice', [
                'choices' => [
                    'AR' => 'Argentina',
                    'BR' => 'Brazil',
                    'MX' => 'Mexico',
                    'CL' => 'Chile',
                    'CO' => 'Colombia',
                    'PE' => 'Peru',
                    'VE' => 'Venezuela'
                ]
            ]);
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults([
            'required'  => false,
            'validation_groups' => ['Default', 'AmountAstropay'],
        ]);
    }

    /**
     * Returns the name of this type.
     *
     * @return string The name of this type
     */
    public function getName()
    {
        return 'btc_transfer_astropay_deposit';
    }
}
