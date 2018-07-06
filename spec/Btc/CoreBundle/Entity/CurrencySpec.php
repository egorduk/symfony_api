<?php

namespace spec\Btc\CoreBundle\Entity;

use Btc\CoreBundle\Entity\Currency;
use Btc\CoreBundle\Entity\RestEntityInterface;
use PhpSpec\ObjectBehavior;

class CurrencySpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->shouldHaveType(Currency::class);
        $this->shouldImplement(RestEntityInterface::class);
    }
}
