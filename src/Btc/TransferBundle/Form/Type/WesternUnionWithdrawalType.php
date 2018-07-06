<?php

namespace Btc\TransferBundle\Form\Type;

use Btc\CoreBundle\Entity\User;
use Symfony\Component\Form\FormBuilderInterface;

class WesternUnionWithdrawalType extends ManualWithdrawalType
{
    /**
     * Available country list to choose from.
     *
     * @var \Btc\CoreBundle\Entity\Country[]
     */
    private $countries;

    public function __construct(User $user, $countries = [])
    {
        parent::__construct($user);
        $this->countries = $countries;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        $builder
            ->add('firstname', 'text', ['label' => 'manual.firstname'])
            ->add('lastname', 'text', ['label' => 'manual.lastname'])
            ->add('address', 'text', ['label' => 'manual.address'])
            ->add('province', 'text', ['label' => 'manual.province'])
            ->add(
                'country',
                'entity',
                [
                    'class' => 'BtcCoreBundle:Country',
                    'choices' => $this->countries,
                    'property' => 'name',
                    'empty_value' => 'Select Country',
                    'label' => 'manual.country'
                ]
            )
            ->add('phone', 'text', ['label' => 'manual.phone']);

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
        return ['Default', 'Amount', 'WU'];
    }
}