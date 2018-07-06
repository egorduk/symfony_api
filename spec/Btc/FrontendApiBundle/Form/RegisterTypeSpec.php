<?php

namespace spec\Btc\FrontendApiBundle\Form;

use Btc\FrontendApiBundle\Form\RegisterType;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class RegisterTypeSpec extends ObjectBehavior
{
    public function it_is_a_form()
    {
        $this->shouldHaveType(RegisterType::class);
        $this->shouldHaveType(AbstractType::class);
    }

    public function it_build_form(FormBuilderInterface $builder)
    {
        $builder->add('email', Argument::exact('email'))->willReturn($builder)->shouldBeCalled();
        $builder->add('newsletter', Argument::exact('checkbox'), Argument::type('array'))->willReturn($builder)->shouldBeCalled();

        $this->buildForm($builder, []);
    }

    public function it_has_empty_name()
    {
        $this->getName()->shouldBe('');
    }
}
