<?php

namespace spec\Btc\CoreBundle\Validator\Constraints;

use Btc\CoreBundle\Entity\User;
use Btc\CoreBundle\Validator\Constraints\Totp;
use Btc\CoreBundle\Validator\Constraints\TotpValidator;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class TotpValidatorSpec extends ObjectBehavior
{
    const SEED = '3132333435363738393031323334353637383930';
    const VALID_OTP = '287082';
    const INVALID_OTP = 'INVALIDOTP';

    public function let(ExecutionContextInterface $context, \DateTime $dateTime, User $user)
    {
        $dateTime->getTimestamp()
            ->willReturn(strtotime('1970-01-01 00:00:59 UTC'));

        $this->beConstructedWith($dateTime);

        $this->initialize($context);

        $user->getAuthKey()->willReturn(self::SEED);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(TotpValidator::class);
        $this->shouldHaveType(ConstraintValidator::class);
    }

    public function it_should_validate_otp(User $user, Constraint $constraint, ExecutionContextInterface $context)
    {
        $user->getAuthCode()->shouldBeCalled()->willReturn(self::VALID_OTP);
        $user->hasTOTP()->willReturn(true);

        $context->addViolation(Argument::any())->shouldNotBeCalled();

        $this->validate($user, $constraint);
    }

    public function it_should_add_a_violation_on_invalid_otp(User $user, Totp $constraint, ExecutionContextInterface $context)
    {
        $user->getAuthCode()->shouldBeCalled()->willReturn(self::INVALID_OTP);
        $user->hasTOTP()->willReturn(true);

        $context->addViolation(Argument::any(), Argument::any())->shouldBeCalled();

        $this->validate($user, $constraint);
    }
}
