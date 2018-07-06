<?php

namespace spec\Btc\FrontendApiBundle\Service;

use Btc\FrontendApiBundle\Service\SmsService;
use PhpSpec\ObjectBehavior;

class SmsServiceSpec extends ObjectBehavior
{
    public function let(\Services_Twilio $sender, $from = 'from')
    {
        $this->beConstructedWith($sender, $from);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(SmsService::class);
    }
}
