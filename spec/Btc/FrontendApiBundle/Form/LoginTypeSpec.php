<?php

namespace spec\Btc\FrontendApiBundle\Form;

use Btc\FrontendApiBundle\Form\LoginType;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class LoginTypeSpec extends ObjectBehavior
{
    public function it_is_a_form()
    {
        $this->shouldHaveType(LoginType::class);
        $this->shouldHaveType(AbstractType::class);
    }

    public function it_build_form(FormBuilderInterface $builder)
    {
        $builder->add('email', Argument::exact('email'), Argument::type('array'))->willReturn($builder)->shouldBeCalled();
        $builder->add('password', Argument::exact('password'), Argument::type('array'))->willReturn($builder)->shouldBeCalled();

        $this->buildForm($builder, []);
    }

    public function it_has_empty_name()
    {
        $this->getName()->shouldBe('');
    }
}
