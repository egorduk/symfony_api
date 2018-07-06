<?php

namespace Btc\TransferBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Btc\CoreBundle\Entity\User;

class VirtualWithdrawalType extends AbstractType
{
    private $user;

    public function __construct(User $user)
    {
        $this->user = $user;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('amount', 'money', [
                'currency' => false,
                'precision' => 8,
            ])
            ->add('foreignAccount', 'text', [
                'label' => 'withdrawal.form.foreign_account',
            ]);

        if ($this->user->hasTOTP() || $this->user->hasHOTP()) {
            $builder->add('authCode', 'text', [
                'label' => 'withdrawal.form.auth_code',
            ]);
        }

        if ($this->user->hasHOTP()) {
            $builder->add('sendSms', 'submit', [
                'label' => 'withdrawal.send_sms',
                'attr' => ['class' => 'btn btn-blue btn-control btn-block'],
            ]);
        }

        $builder->add('save', 'submit', [
            'label' => 'withdrawal.submit',
            'attr' => ['class' => 'btn btn-blue btn-control btn-block'],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'translation_domain' => 'Withdrawal',
            'validation_groups' => function(FormInterface $form) {
                if ($this->user->hasHOTP() && $form->get('sendSms')->isClicked()) {
                    return ['novalidation'];
                }

                return ['Account', 'Default', 'AmountVirtual'];
            },
            'required' => false,
            'allow_extra_fields' => true,
            'csrf_protection' => false,
        ]);
    }

    public function getName()
    {
        return 'btc_transfer_withdrawal_crypto';
    }
}
