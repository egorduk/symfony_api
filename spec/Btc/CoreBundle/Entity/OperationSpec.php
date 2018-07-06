<?php

namespace spec\Btc\CoreBundle\Entity;

use Btc\CoreBundle\Entity\Operation;
use PhpSpec\ObjectBehavior;

class OperationSpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->shouldHaveType(Operation::class);
    }
}
