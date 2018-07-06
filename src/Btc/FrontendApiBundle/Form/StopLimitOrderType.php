<?php

namespace Btc\FrontendApiBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class StopLimitOrderType extends AbstractType
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
            ])
            ->add('askedUnitPrice', 'money', [
                'currency' => false,
                'precision' => 8,
                'required' => false,
            ])
            ->add('stopPrice', 'money', [
                'currency' => false,
                'precision' => 8,
                'required' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => $this->dataClass,
            'allow_extra_fields' => true,
            'csrf_protection' => false,
        ));
    }

    public function getName()
    {
        return '';
    }
}
