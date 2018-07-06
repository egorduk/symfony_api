<?php

namespace spec\Btc\CoreBundle\Entity;

use Btc\CoreBundle\Entity\RestEntityInterface;
use Btc\CoreBundle\Entity\UserFeeSet;
use PhpSpec\ObjectBehavior;

class UserFeeSetSpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->shouldHaveType(UserFeeSet::class);
        $this->shouldImplement(RestEntityInterface::class);
    }
}
