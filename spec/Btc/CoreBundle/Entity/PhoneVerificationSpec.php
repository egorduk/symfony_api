<?php

namespace spec\Btc\CoreBundle\Entity;

use Btc\CoreBundle\Entity\PhoneVerification;
use Btc\FrontendApiBundle\Classes\SpecValidatorTrait;
use PhpSpec\ObjectBehavior;
use Symfony\Component\Validator\Constraints\NotBlank;

class PhoneVerificationSpec extends ObjectBehavior
{
    use SpecValidatorTrait;

    public function let()
    {
        $this->initValidator();
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(PhoneVerification::class);
    }

    public function it_is_confirmed_and_sent_are_false_by_default()
    {
        $this->isConfirmed()->shouldBe(false);
        $this->isSent()->shouldBe(false);
    }

    public function it_is_sent()
    {
        $this->setSent(true);
        $this->isSent()->shouldBe(true);
    }

    public function it_is_confirmed()
    {
        $this->confirm();
        $this->isConfirmed()->shouldBe(true);
    }

    public function it_should_not_allow_blank_phone_for_group_api()
    {
        $violations = $this->validator->validate(new PhoneVerification(), ['api']);

        $notBlankConstraint = new NotBlank();

        $this->shouldHaveViolation($violations, $notBlankConstraint->message);
    }
}
