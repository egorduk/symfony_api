<?php

namespace Btc\TransferBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\FormInterface;
use Btc\CoreBundle\Entity\User;

class WithdrawalType extends AbstractType
{
    private $user;

    public function __construct(User $user)
    {
        $this->user = $user;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('amount', 'money', [
                'currency' => false,
            ])
            ->add('currency', 'currency_selector')
            ->add('foreignAccount', 'text');

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
            'required'  => false,
            'translation_domain' => 'Withdrawal',
            'validation_groups' => function(FormInterface $form) {
                if ($this->user->hasHOTP() && $form->get('sendSms')->isClicked()) {
                    return ['novalidation'];
                }
                return ['Account', 'Default', 'Amount'];
            },
        ]);
    }

    public function getName()
    {
        return 'btc_transfer_withdrawal';
    }
}
