<?php

namespace spec\Btc\FrontendApiBundle\Form;

use Btc\FrontendApiBundle\Form\BusinessInfoType;
use Btc\FrontendApiBundle\Form\PersonalInfoType;
use Btc\FrontendApiBundle\Form\UserVerificationType;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class UserVerificationTypeSpec extends ObjectBehavior
{
    public function it_is_a_form()
    {
        $this->shouldHaveType(UserVerificationType::class);
        $this->shouldHaveType(AbstractType::class);
    }

    public function it_build_form(FormBuilderInterface $builder)
    {
        $builder->add('personalInfo', Argument::type(PersonalInfoType::class), Argument::type('array'))->willReturn($builder)->shouldBeCalled();
        $builder->add('businessInfo', Argument::type(BusinessInfoType::class), Argument::type('array'))->willReturn($builder)->shouldBeCalled();

        $this->buildForm($builder, []);
    }

    public function it_has_empty_name()
    {
        $this->getName()->shouldBe('');
    }
}
