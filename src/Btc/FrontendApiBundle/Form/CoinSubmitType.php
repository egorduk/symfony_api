<?php

namespace Btc\FrontendApiBundle\Form;

use Btc\FrontendApiBundle\Entity\CoinSubmit;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;


class CoinSubmitType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $constraints = [
            'constraints' => [
                new NotBlank(),
                new Length(['max' => 255]),
            ]];
        $builder
            ->add('blockchain', 'text', $constraints)
            ->add('isListingToken', 'text', $constraints)
            ->add('projectLink', 'text', $constraints)
            ->add('representativeEmail', 'email', $constraints)
            ->add('representativeName', 'text', $constraints)
            ->add('representativePosition', 'text', $constraints)
            ->add('socialThreads', 'text', $constraints)
            ->add('tokenName', 'text', $constraints)
            ->add('tokenTicker', 'text', $constraints)
            ->add('tokenSupply', 'text')
            ->add('icoTokenPrice', 'text')
            ->add('saleEnd', 'text')
            ->add('saleEndTime', 'text')
            ->add('saleStart', 'text')
            ->add('saleStartTime', 'text');
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => CoinSubmit::class,
            'csrf_protection' => false,
            'allow_extra_fields' => true,
        ]);
    }

    public function getName()
    {
        return 'rest_coin_submit_form';
    }
}
