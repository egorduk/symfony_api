<?php

namespace spec\Btc\FrontendApiBundle\Service;

use Btc\FrontendApiBundle\Service\PinInterface;
use Btc\FrontendApiBundle\Service\PinService;
use PhpSpec\ObjectBehavior;

class PinServiceSpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->shouldHaveType(PinService::class);
        $this->shouldImplement(PinInterface::class);
    }

    public function it_has_correct_pin_length()
    {
        $this->generate(6)->shouldMatch('/^[0-9]{6}$/');
    }

    public function it_is_encode_hash_sha256()
    {
        $this->encodePin(1, 1)->shouldBe(hash('sha256', 11));
    }

    public function it_is_valid_pin()
    {
        $this->isPinValid(hash('sha256', 11), 1, 1)->shouldReturn(true);
    }
}
