<?php

namespace spec\Btc\Component\Market\Service;

use Btc\Component\Market\Service\FeeService;
use Doctrine\ORM\EntityManager;
use PhpSpec\ObjectBehavior;

class FeeServiceSpec extends ObjectBehavior
{
    function let(EntityManager $em)
    {
        $this->beConstructedWith($em);
    }

    function it_is_initializable()
    {
        $this->shouldHaveType(FeeService::class);
    }

}
