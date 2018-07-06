<?php

namespace spec\Btc\CoreBundle\Entity;

use Btc\CoreBundle\Entity\FeeAction;
use PhpSpec\ObjectBehavior;

class FeeActionSpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->shouldHaveType(FeeAction::class);
    }

    public function it_is_set_for_market()
    {
        $this->setForMarket(1);
        $this->isForMarket()->shouldBe(true);
    }
    public function it_is_not_set_for_market()
    {
        $this->setForMarket(0);
        $this->isForMarket()->shouldBe(false);
    }
}
