<?php

namespace spec\Btc\CoreBundle\Entity;

use Btc\CoreBundle\Entity\UserBusinessInfo;
use Btc\CoreBundle\Entity\UserPersonalInfo;
use Btc\CoreBundle\Entity\Verification;
use Btc\FrontendApiBundle\Classes\SpecValidatorTrait;
use PhpSpec\ObjectBehavior;

class VerificationSpec extends ObjectBehavior
{
    use SpecValidatorTrait;

    public function let(UserBusinessInfo $userBusinessInfo, UserPersonalInfo $userPersonalInfo)
    {
        $this->initValidator();

        $this->setBusinessInfo($userBusinessInfo);
        $this->setPersonalInfo($userPersonalInfo);
    }

    public function it_should_be_initializable()
    {
        $this->shouldHaveType(Verification::class);
    }

    public function it_should_be_return_correct_user_info(UserBusinessInfo $userBusinessInfo, UserPersonalInfo $userPersonalInfo)
    {
        $this->getBusinessInfo()->shouldReturn($userBusinessInfo);
        $this->getPersonalInfo()->shouldReturn($userPersonalInfo);
    }
}
