<?php

namespace spec\Btc\CoreBundle\Util;

use Btc\CoreBundle\Util\SeedGenerator;
use Btc\CoreBundle\Util\SeedGeneratorInterface;
use PhpSpec\ObjectBehavior;

class SeedGeneratorSpec extends ObjectBehavior
{
    public function it_is_seed_generator()
    {
        $this->shouldHaveType(SeedGenerator::class);
        $this->shouldHaveType(SeedGeneratorInterface::class);
    }

    public function it_should_return_string_value()
    {
        $this->getSeed()->shouldMatch('/^[\S]+$/');
    }
}
