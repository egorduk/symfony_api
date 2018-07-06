<?php

namespace spec\Btc\FrontendApiBundle\Service;

use Btc\FrontendApiBundle\Repository\MarketRepository;
use Btc\FrontendApiBundle\Service\MarketGroupService;
use Btc\FrontendApiBundle\Service\RestRedis;
use PhpSpec\ObjectBehavior;

class MarketGroupServiceSpec extends ObjectBehavior
{
    public function let(MarketRepository $markets)
    {
        $this->beConstructedWith(new RestRedis('127.0.0.1', '6379'), $markets);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(MarketGroupService::class);
    }

    public function it_get_empty_market_list_with_last_prices(MarketRepository $marketRepository)
    {
        $marketRepository->findAllForTrading()->willReturn([]);

        $this->getMarketListWithLastPrices()->shouldBe([]);
    }
}
