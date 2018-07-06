<?php

namespace spec\Btc\FrontendApiBundle\Form;

use Btc\FrontendApiBundle\Form\PhonePinType;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class PhonePinTypeSpec extends ObjectBehavior
{
    public function it_is_a_form()
    {
        $this->shouldHaveType(PhonePinType::class);
        $this->shouldHaveType(AbstractType::class);
    }

    public function it_build_form(FormBuilderInterface $builder)
    {
        $builder->add('pin', Argument::type('string'), Argument::type('array'))->willReturn($builder)->shouldBeCalled();

        $this->buildForm($builder, []);
    }

    public function it_has_empty_name()
    {
        $this->getName()->shouldBe('');
    }
}
