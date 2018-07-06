<?php

namespace spec\Btc\CoreBundle\Entity;

use Btc\CoreBundle\Entity\Country;
use Btc\CoreBundle\Entity\RestEntityInterface;
use PhpSpec\ObjectBehavior;

class CountrySpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->shouldHaveType(Country::class);
        $this->shouldImplement(RestEntityInterface::class);
    }
}
