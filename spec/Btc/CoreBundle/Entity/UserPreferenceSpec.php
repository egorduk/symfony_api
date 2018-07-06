<?php

namespace spec\Btc\CoreBundle\Entity;

use Btc\CoreBundle\Entity\UserPreference;
use PhpSpec\ObjectBehavior;

class UserPreferenceSpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->shouldHaveType(UserPreference::class);
    }
}
