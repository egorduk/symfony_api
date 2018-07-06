<?php

namespace Btc\TransferBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Doctrine\ORM\EntityManager;
use Btc\TransferBundle\Form\DataTransformer\CurrencyToCodeTransformer;

class CurrencySelectorType extends AbstractType
{
    private $em;
    private $currencies;

    public function __construct(EntityManager $em, array $currencies)
    {
        $this->em = $em;
        $this->currencies = array_combine($currencies, $currencies);
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $transformer = new CurrencyToCodeTransformer($this->em);
        $builder->addModelTransformer($transformer);
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults([
            'choices' => $this->currencies,
            'invalid_message' => 'The selected currency does not exist',
        ]);
    }

    public function getParent()
    {
        return 'choice';
    }

    public function getName()
    {
        return 'currency_selector';
    }
}
