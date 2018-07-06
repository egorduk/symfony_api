<?php

namespace Btc\TransferBundle\Form\Type;

use Btc\CoreBundle\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class InternationalWireWithdrawalType extends AbstractType
{
    /**
     * @var array list of countries
     */
    private $countries;

    public function __construct(User $user, array $countries)
    {
        $this->countries = $countries;
        $this->user = $user;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
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

        $builder->add('amount', 'money', [
            'label' => 'wire.amount',
            'currency' => false,
        ]);

        $builder->add('currency', 'currency_selector');

        $builder->add(
            'beneficiaryAccountNumber',
            'text',
            ['label' => 'wire.beneficiary_account_number']
        );

        $builder->add(
            'beneficiaryAccountName',
            'text',
            ['label' => 'wire.beneficiary_account_name']
        );

        $builder->add(
            'beneficiaryAccountAddress',
            'text',
            ['label' => 'wire.beneficiary_account_address']
        );

        $builder->add(
            'beneficiaryAccountCity',
            'text',
            ['label' => 'wire.beneficiary_account_city']
        );

        $builder->add(
            'beneficiaryAccountState',
            'text',
            ['label' => 'wire.beneficiary_account_state']
        );

        $builder->add(
            'beneficiaryAccountPostalCode',
            'text',
            ['label' => 'wire.beneficiary_account_postal_code']
        );

        $builder->add(
            'beneficiaryAccountCountry',
            'entity',
            [
                'class' => 'BtcCoreBundle:Country',
                'choices' => $this->countries,
                'property' => 'name',
                'empty_value' => 'Select Country',
                'label' => 'wire.beneficiary_account_country',
            ]
        );

        $builder->add(
            'beneficiaryBankCode',
            'text',
            ['label' => 'wire.beneficiary_bank_code']
        );

        $builder->add(
            'beneficiaryBankName',
            'text',
            ['label' => 'wire.beneficiary_bank_name']
        );

        $builder->add(
            'beneficiaryBankAddress',
            'text',
            ['label' => 'wire.beneficiary_bank_address']
        );

        $builder->add(
            'beneficiaryBankCity',
            'text',
            ['label' => 'wire.beneficiary_bank_city']
        );

        $builder->add(
            'beneficiaryBankState',
            'text',
            ['label' => 'wire.beneficiary_bank_state']
        );

        $builder->add(
            'beneficiaryBankPostalCode',
            'text',
            ['label' => 'wire.beneficiary_bank_postal_code']
        );

        $builder->add(
            'beneficiaryBankCountry',
            'entity',
            [
                'class' => 'BtcCoreBundle:Country',
                'choices' => $this->countries,
                'property' => 'name',
                'empty_value' => 'Select Country',
                'label' => 'wire.beneficiary_bank_country',
            ]
        );

        $builder->add(
            'correspondentBankAccount',
            'text',
            ['label' => 'wire.correspondent_bank_account']
        );

        $builder->add(
            'correspondentBankCode',
            'text',
            ['label' => 'wire.correspondent_bank_code']
        );

        $builder->add(
            'correspondentBankName',
            'text',
            ['label' => 'wire.correspondent_bank_name']
        );

        $builder->add(
            'correspondentBankAddress',
            'text',
            ['label' => 'wire.correspondent_bank_address']
        );

        $builder->add(
            'correspondentBankCity',
            'text',
            ['label' => 'wire.correspondent_bank_city']
        );

        $builder->add(
            'correspondentBankState',
            'text',
            ['label' => 'wire.correspondent_bank_state']
        );

        $builder->add(
            'correspondentBankPostalCode',
            'text',
            ['label' => 'wire.correspondent_bank_postal_code']
        );

        $builder->add(
            'correspondentBankCountry',
            'entity',
            [
                'class' => 'BtcCoreBundle:Country',
                'choices' => $this->countries,
                'property' => 'name',
                'empty_value' => 'Select Country',
                'label' => 'wire.correspondent_bank_country',
                'required'=> false
            ]
        );

        $builder->add(
            'comment',
            'textarea',
            ['label' => 'wire.comment']
        );
    }

    /**
     * Returns the name of this type.
     *
     * @return string The name of this type
     */
    public function getName()
    {
        return 'international_transfer_withdrawal';
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'required' => false,
            'validation_groups' => function(FormInterface $form) {
                if ($this->user->hasHOTP() && $form->get('sendSms')->isClicked()) {
                    return ['novalidation'];
                }
                return ['Default', 'AmountWire'];
            },
            'translation_domain' => 'Withdrawal',
            'allow_extra_fields' => true,
        ]);
    }
}
