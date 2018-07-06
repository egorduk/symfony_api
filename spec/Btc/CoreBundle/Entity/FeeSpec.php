<?php

namespace spec\Btc\CoreBundle\Entity;

use Btc\CoreBundle\Entity\Fee;
use PhpSpec\ObjectBehavior;

class FeeSpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->shouldHaveType(Fee::class);
    }
}
