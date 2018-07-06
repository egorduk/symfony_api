<?php

namespace Btc\FrontendApiBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class MarketOrderType extends AbstractType
{
    private $side;
    private $dataClass;

    public function __construct($side, $dataClass)
    {
        $this->side = $side;
        $this->dataClass = $dataClass;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('amount', 'money', [
                'currency' => false,
                'precision' => 8,
                'required' => false,
                'label' => 'form.market.amount',
                'translation_domain' => 'Trade'
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => $this->dataClass,
            'intention'  => 'market_order',
            'allow_extra_fields' => true,
            'csrf_protection' => false,
        ]);
    }

    public function getName()
    {
        return '';
    }
}
