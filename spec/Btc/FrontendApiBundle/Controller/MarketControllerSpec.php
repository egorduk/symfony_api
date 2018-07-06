<?php

namespace spec\Btc\FrontendApiBundle\Controller;

use Btc\CoreBundle\Entity\Market;
use Btc\FrontendApiBundle\Controller\MarketController;
use Btc\FrontendApiBundle\Exception\Rest\NotFoundException;
use Btc\FrontendApiBundle\Service\MarketGroupService;
use Btc\FrontendApiBundle\Service\MarketService;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Request\ParamFetcherInterface;
use FOS\RestBundle\View\View;
use FOS\RestBundle\View\ViewHandler;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;

class MarketControllerSpec extends ObjectBehavior
{
    public function let(
        ContainerInterface $container,
        MarketService $marketService,
        ViewHandler $viewHandler,
        MarketGroupService $marketGroupService
    ) {
        $this->setContainer($container);

        $container->get('rest.service.market')->willReturn($marketService);
        $container->get('rest.service.market_grouping')->willReturn($marketGroupService);
        $container->get('fos_rest.view_handler')->willReturn($viewHandler);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(MarketController::class);
        $this->shouldHaveType(FOSRestController::class);
    }

    public function it_should_respond_to_get_markets_action(
        Response $response,
        ViewHandler $viewHandler,
        MarketService $marketService,
        ParamFetcherInterface $paramFetcher
    ) {
        $marketService->all(Argument::any(), Argument::any())->willReturn([new Market()]);

        $viewHandler->handle(Argument::type(View::class))->willReturn($response);

        $response = $this->getMarketsAction($paramFetcher);
        $response->shouldHaveType(Response::class);
    }

    public function it_should_respond_to_get_market_by_slug_action(
        Response $response,
        ViewHandler $viewHandler,
        MarketService $marketService,
        Market $market
    ) {
        $marketService->getOneBy(['slug' => 'slug'])->willReturn($market);

        $viewHandler->handle(Argument::type(View::class))->willReturn($response);

        $response = $this->getMarketBySlugAction('slug');
        $response->shouldHaveType(Response::class);
    }

    public function it_should_respond_to_get_market_prices_action(
        Response $response,
        ViewHandler $viewHandler,
        MarketGroupService $marketGroupService
    ) {
        $marketGroupService->getMarketListWithLastPrices()->willReturn([]);

        $viewHandler->handle(Argument::type(View::class))->willReturn($response);

        $response = $this->getMarketPricesAction();
        $response->shouldHaveType(Response::class);
    }

    public function it_throws_an_exception_if_items_not_found(MarketService $marketService, ParamFetcherInterface $paramFetcher)
    {
        $marketService->all(Argument::any(), Argument::any())->willReturn([]);

        $this
            ->shouldThrow(NotFoundException::class)
            ->duringGetMarketsAction($paramFetcher);
    }

    public function it_throws_an_exception_if_item_not_found(MarketService $marketService)
    {
        $marketService->getOneBy(['slug' => 'slug'])->willReturn([]);

        $this
            ->shouldThrow(NotFoundException::class)
            ->duringGetMarketBySlugAction('slug');
    }
}
