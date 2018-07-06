<?php

namespace Btc\FrontendApiBundle\Form\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;

class NumberToBooleanTransformer implements DataTransformerInterface
{
    public function transform($value)
    {
        return (bool) $value;
    }

    public function reverseTransform($value)
    {
        return intval($value);
    }
}
