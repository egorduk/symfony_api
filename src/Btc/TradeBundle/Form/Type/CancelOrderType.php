<?php

namespace Btc\TradeBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class CancelOrderType extends AbstractType
{
    private $name;

    public function __construct($name)
    {
        $this->name = $name;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('id', 'hidden');
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults([
            'data_class' => 'Btc\TradeBundle\Model\CancelDeal',
            'intention'  => 'cancel_order',
        ]);
    }

    public function getName()
    {
        return $this->name;
    }
}
