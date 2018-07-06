<?php

namespace spec\Btc\FrontendApiBundle\Form;

use Btc\FrontendApiBundle\Form\PreferencesType;
use Btc\FrontendApiBundle\Form\SettingType;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class PreferencesTypeSpec extends ObjectBehavior
{
    public function it_is_a_form()
    {
        $this->shouldHaveType(PreferencesType::class);
        $this->shouldHaveType(AbstractType::class);
    }

    public function it_build_form(FormBuilderInterface $builder)
    {
        $builder->add('rows', Argument::exact('collection'), ['type' => new SettingType()])->willReturn($builder)->shouldBeCalled();

        $this->buildForm($builder, []);
    }

    public function it_has_empty_name()
    {
        $this->getName()->shouldBe('');
    }
}
