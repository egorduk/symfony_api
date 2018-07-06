<?php

namespace spec\Btc\CoreBundle\Entity;

use Btc\CoreBundle\Entity\Activity;
use PhpSpec\ObjectBehavior;

class ActivitySpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->shouldHaveType(Activity::class);
    }
}
