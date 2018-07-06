<?php

namespace Btc\TransferBundle\Form\Type;

use Symfony\Component\Form\FormBuilderInterface;

class PayPalWithdrawalType extends ManualWithdrawalType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        $builder
            ->add('foreignAccount', 'text', ['label' => 'manual.paypal_foreign_account'])
            ->add('firstname', 'text', ['label' => 'manual.firstname'])
            ->add('lastname', 'text', ['label' => 'manual.lastname']);

        $builder->add('save', 'submit', [
            'label' => 'withdrawal.submit',
            'attr' => ['class' => 'btn btn-blue btn-control btn-block'],
        ]);
    }

    /**
     * @return array Validation groups
     */
    protected function getValidationGroups()
    {
        return ['Account', 'Default', 'Amount', 'PayPal'];
    }
}
