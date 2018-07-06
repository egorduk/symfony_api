<?php

namespace spec\Btc\CoreBundle\Entity;

use Btc\CoreBundle\Entity\CurrencyRate;
use PhpSpec\ObjectBehavior;

class CurrencyRateSpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->shouldHaveType(CurrencyRate::class);
    }
}
