<?php

namespace spec\Btc\FrontendApiBundle\Form\DataTransformer;

use Btc\FrontendApiBundle\Form\DataTransformer\NumberToBooleanTransformer;
use PhpSpec\ObjectBehavior;
use Symfony\Component\Form\DataTransformerInterface;

class NumberToBooleanTransformerSpec extends ObjectBehavior
{
    public function it_is_a_transformer()
    {
        $this->shouldHaveType(NumberToBooleanTransformer::class);
        $this->shouldHaveType(DataTransformerInterface::class);
    }

    public function it_return_transform()
    {
        $this->transform(1)->shouldBe(true);
    }

    public function it_return_reverse_transform()
    {
        $this->reverseTransform(true)->shouldBe(1);
    }
}
