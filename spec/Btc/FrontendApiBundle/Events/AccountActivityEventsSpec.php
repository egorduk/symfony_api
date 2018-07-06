<?php

namespace spec\Btc\FrontendApiBundle\Events;

use Btc\FrontendApiBundle\Events\AccountActivityEvents;
use PhpSpec\ObjectBehavior;

class AccountActivityEventsSpec extends ObjectBehavior
{
    public function it_is_an_event()
    {
        $this->shouldHaveType(AccountActivityEvents::class);
    }
}
