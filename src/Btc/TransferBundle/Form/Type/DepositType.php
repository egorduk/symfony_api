<?php

namespace Btc\TransferBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DepositType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('amount', 'money', [
                'currency' => false,
            ])
            ->add('currency', 'currency_selector');
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'required'  => false,
            'validation_groups' => ['Default', 'Amount'],
        ]);
    }

    public function getName()
    {
        return 'btc_transfer_deposit';
    }
}
