<?php

namespace spec\Btc\CoreBundle\Entity;

use Btc\CoreBundle\Entity\Bank;
use Btc\CoreBundle\Entity\RestEntityInterface;
use PhpSpec\ObjectBehavior;

class BankSpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->shouldHaveType(Bank::class);
        $this->shouldImplement(RestEntityInterface::class);
    }
}
