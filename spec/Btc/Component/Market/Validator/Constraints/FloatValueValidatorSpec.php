<?php

namespace spec\Btc\Component\Market\Validator\Constraints;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Symfony\Component\Validator\ExecutionContextInterface;
use Btc\Component\Market\Validator\Constraints\FloatValue;

class FloatValueValidatorSpec extends ObjectBehavior
{
    function let(ExecutionContextInterface $context)
    {
        $this->initialize($context);
    }

    function it_should_not_validate_too_big_float($context, FloatValue $constraint)
    {
        $context->addViolation('float.overflow')->shouldBeCalled();
        $this->validate(878978979878987979789797898798789798797979797979, $constraint);
    }

    function it_should_not_validate_too_small_float_value($context, FloatValue $constraint)
    {
        $context->addViolation('float.overflow')->shouldBeCalled();
        $this->validate(-878978979878987979789797898798789798797979797979.8888, $constraint);
    }

    function it_should_skip_zero_value($context, FloatValue $constraint)
    {
        $context->addViolation('float.overflow')->shouldNotBeCalled();
        $this->validate(0.0, $constraint);
        $this->validate(0, $constraint);
    }

    function it_should_allow_small_values($context, FloatValue $constraint)
    {
        $context->addViolation('float.overflow')->shouldNotBeCalled();
        $this->validate(0.01, $constraint);
    }

    function it_should_allow_normal_value($context, FloatValue $constraint)
    {
        $context->addViolation('float.overflow')->shouldNotBeCalled();
        $this->validate(87897.66666, $constraint);
    }

    function it_should_not_allow_numbers_higher_than_eight_digits($context, FloatValue $constraint)
    {
        $context->addViolation('float.overflow')->shouldBeCalled();
        $this->validate(200000000, $constraint);
    }

    function it_should_allow_eight_digit_numbers($context, FloatValue $constraint)
    {
        $context->addViolation('float.overflow')->shouldNotBeCalled();
        $this->validate(99999999.99999999, $constraint);
    }

    function it_should_allow_very_small_numbers($context, FloatValue $constraint)
    {
        $context->addViolation('float.overflow')->shouldNotBeCalled();
        $this->validate(0.00005, $constraint);
    }
}
