<?php

namespace Btc\FrontendApiBundle\Form;

use Btc\FrontendApiBundle\Form\DataTransformer\NumberToBooleanTransformer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class BooleanType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $transformer = new NumberToBooleanTransformer();
        $builder->addModelTransformer($transformer);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'invalid_message' => 'Error',
        ]);
    }

    public function getParent()
    {
        return 'checkbox';
    }

    public function getName()
    {
        return 'boolean_type';
    }
}
