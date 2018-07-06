<?php

namespace Btc\TransferBundle\Form\Type;

use Btc\CoreBundle\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Form\FormInterface;

abstract class ManualWithdrawalType extends AbstractType
{
    /**
     * @var User
     */
    private $user;

    public function __construct(User $user)
    {
        $this->user = $user;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('amount', 'money', ['currency' => false])
            ->add('currency', 'currency_selector');

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
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(
            [
                'required' => false,
                'translation_domain' => 'Withdrawal',
                'validation_groups' => function(FormInterface $form) {
                    if ($this->user->hasHOTP() && $form->get('sendSms')->isClicked()) {
                        return ['novalidation'];
                    }
                    return $this->getValidationGroups();
                },
            ]
        );
    }

    /**
     * @return array Validation groups
     */
    abstract protected function getValidationGroups();

    /**
     * Returns the name of this type.
     *
     * @return string The name of this type
     */
    public function getName()
    {
        return 'btc_transfer_withdrawal_manual';
    }
}
