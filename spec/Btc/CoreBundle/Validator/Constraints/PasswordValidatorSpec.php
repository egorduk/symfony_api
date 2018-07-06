<?php

namespace spec\Btc\CoreBundle\Validator\Constraints;

use Btc\CoreBundle\Validator\Constraints\Password;
use Btc\CoreBundle\Validator\Constraints\PasswordValidator;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class PasswordValidatorSpec extends ObjectBehavior
{
    public function let(ExecutionContextInterface $context)
    {
        $this->initialize($context);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(PasswordValidator::class);
    }

    public function it_should_be_a_constraint_validator()
    {
        $this->shouldHaveType(ConstraintValidator::class);
    }

    public function it_should_require_upperlowercase_and_a_digit(ExecutionContextInterface $context, Password $constraint)
    {
        $c1 = 'aB1';
        $c2 = 'Ba1';
        $c3 = '1Ba';
        $c4 = '1aB';

        $context->addViolation(Argument::any(), Argument::any())->shouldNotBeCalled();

        $this->validate($c1, $constraint);
        $this->validate($c2, $constraint);
        $this->validate($c3, $constraint);
        $this->validate($c4, $constraint);
    }

    public function it_should_require_lowercase(ExecutionContextInterface $context, Password $constraint)
    {
        $c1 = 'ADZXCVBASDG1';

        $context->addViolation(Argument::any(), Argument::any())->shouldBeCalled();
        $this->validate($c1, $constraint);
    }

    public function it_should_require_uppercase(ExecutionContextInterface $context, Password $constraint)
    {
        $c1 = 'asdvjahdfawkhgej1';

        $context->addViolation(Argument::any(), Argument::any())->shouldBeCalled();
        $this->validate($c1, $constraint);
    }

    public function it_should_require_number(ExecutionContextInterface $context, Password $constraint)
    {
        $c1 = 'AbxhjAJSHg';

        $context->addViolation(Argument::any(), Argument::any())->shouldBeCalled();
        $this->validate($c1, $constraint);
    }

    public function it_should_accept_in_addition_special_symbols(ExecutionContextInterface $context, Password $constraint)
    {
        $c1 = 'AvxGa%68aa91GVHD*&^%#$%^&*()+_1=cAd';

        $context->addViolation(Argument::any(), Argument::any())->shouldNotBeCalled();
        $this->validate($c1, $constraint);
    }
}
