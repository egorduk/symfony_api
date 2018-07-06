<?php

namespace spec\Btc\FrontendApiBundle\Form;

use Btc\FrontendApiBundle\Form\SettingType;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class SettingTypeSpec extends ObjectBehavior
{
    public function it_is_a_form()
    {
        $this->shouldHaveType(SettingType::class);
        $this->shouldHaveType(AbstractType::class);
    }

    public function it_build_form(FormBuilderInterface $builder)
    {
        $builder->add('value', Argument::exact('boolean_type'))->willReturn($builder)->shouldBeCalled();

        $this->buildForm($builder, []);
    }

    public function it_has_empty_name()
    {
        $this->getName()->shouldBe('');
    }
}
