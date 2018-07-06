<?php

namespace spec\Btc\CoreBundle\Entity;

use Btc\CoreBundle\Entity\Settings;
use PhpSpec\ObjectBehavior;

class SettingsSpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->shouldHaveType(Settings::class);
    }
}
