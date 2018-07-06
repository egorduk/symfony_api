<?php

namespace Btc\TransferBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class InternationalWireDepositType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('firstName', 'text', [
            'label' => 'wire.first_name',
            'read_only' => true,
        ]);
        $builder->add('lastName', 'text', [
            'label' => 'wire.last_name',
            'read_only' => true,
        ]);
        $builder->add('companyName', 'text', [
            'label' => 'wire.company',
            'read_only' => true,
        ]);
        $builder->add('amount', 'money', [
            'currency' => false,
            'label' => 'wire.amount',
        ]);
        $builder->add('currency', 'currency_selector');
        $builder->add('comment', 'textarea', [
            'label' => 'wire.comment',
        ]);
    }

    /**
     * Returns the name of this type.
     *
     * @return string The name of this type
     */
    public function getName()
    {
        return 'international_transfer';
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults([
            'required' => false,
            'translation_domain' => 'Deposit',
            'validation_groups' => ['Default', 'AmountWire'],
        ]);
    }
}
