<?php

namespace spec\Btc\CoreBundle\Entity;

use Btc\CoreBundle\Entity\Preference;
use PhpSpec\ObjectBehavior;

class PreferenceSpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->shouldHaveType(Preference::class);
    }
}
