<?php

namespace spec\Btc\CoreBundle\Entity;

use Btc\CoreBundle\Entity\Page;
use PhpSpec\ObjectBehavior;

class PageSpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->shouldHaveType(Page::class);
    }
}
