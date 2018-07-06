<?php

namespace spec\Btc\FrontendApiBundle\Form;

use Btc\FrontendApiBundle\Form\BooleanType;
use Btc\FrontendApiBundle\Form\DataTransformer\NumberToBooleanTransformer;
use PhpSpec\ObjectBehavior;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class BooleanTypeSpec extends ObjectBehavior
{
    public function it_is_a_form()
    {
        $this->shouldHaveType(BooleanType::class);
        $this->shouldHaveType(AbstractType::class);
    }

    public function it_build_form(FormBuilderInterface $builder)
    {
        $builder->addModelTransformer(new NumberToBooleanTransformer())->willReturn($builder)->shouldBeCalled();

        $this->buildForm($builder, []);
    }

    public function it_has_name()
    {
        $this->getName()->shouldBe('boolean_type');
    }

    public function it_has_parent()
    {
        $this->getParent()->shouldBe('checkbox');
    }
}
