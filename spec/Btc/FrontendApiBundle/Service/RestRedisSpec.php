<?php

namespace spec\Btc\FrontendApiBundle\Service;

use Btc\FrontendApiBundle\Service\RestRedis;
use PhpSpec\ObjectBehavior;

class RestRedisSpec extends ObjectBehavior
{
    public function let()
    {
        $this->beConstructedWith('127.0.0.1', '6379', 0);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(RestRedis::class);
        $this->shouldHaveType(\Redis::class);
    }
}
