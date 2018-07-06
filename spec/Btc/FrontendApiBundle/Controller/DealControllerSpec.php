<?php

namespace spec\Btc\FrontendApiBundle\Controller;

use Btc\CoreBundle\Entity\Market;
use Btc\FrontendApiBundle\Controller\DealController;
use Btc\FrontendApiBundle\Exception\Rest\NotFoundException;
use Btc\FrontendApiBundle\Repository\TransactionRepository;
use Btc\FrontendApiBundle\Service\MarketService;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Request\ParamFetcherInterface;
use FOS\RestBundle\View\View;
use FOS\RestBundle\View\ViewHandler;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;

class DealControllerSpec extends ObjectBehavior
{
    public function let(
        ContainerInterface $container,
        MarketService $marketService,
        ViewHandler $viewHandler,
        TransactionRepository $transactionRepository
    ) {
        $this->setContainer($container);

        $container->get('rest.service.market')->willReturn($marketService);
        $container->get('rest.repository.transaction')->willReturn($transactionRepository);
        $container->get('fos_rest.view_handler')->willReturn($viewHandler);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(DealController::class);
        $this->shouldHaveType(FOSRestController::class);
    }

    public function it_should_respond_to_get_deals_action(
        Response $response,
        ViewHandler $viewHandler,
        MarketService $marketService,
        Market $market,
        TransactionRepository $transactionRepository,
        ParamFetcherInterface $paramFetcher
    ) {
        $marketService->all(Argument::any(), Argument::any())->willReturn([new Market()]);

        $transactionRepository->getLatestTransactionsByMarket(Argument::type(Market::class), Argument::any())->willReturn([]);

        $market->getSlug()->willReturn('marketSlug');

        $viewHandler->handle(Argument::type(View::class))->willReturn($response);

        $response = $this->getDealsAction($paramFetcher);
        $response->shouldHaveType(Response::class);
    }

    public function it_should_respond_to_get_deals_by_market_id_action(
        MarketService $marketService,
        TransactionRepository $transactionRepository,
        ViewHandler $viewHandler,
        Response $response
    ) {
        $marketService->get(1)->willReturn(new Market());
        $transactionRepository->getLatestTransactionsByMarket(Argument::type(Market::class), Argument::any())->willReturn([]);

        $viewHandler->handle(Argument::type(View::class))->willReturn($response);

        $response = $this->getDealsByMarketIdAction(1);
        $response->shouldHaveType(Response::class);
    }

    public function it_throws_an_exception_if_market_not_found(MarketService $marketService)
    {
        $marketService->get(0)->willReturn(null);

        $this
            ->shouldThrow(NotFoundException::class)
            ->duringGetDealsByMarketIdAction(0);
    }

    public function it_throws_an_exception_if_markets_not_found(MarketService $marketService, ParamFetcherInterface $paramFetcher)
    {
        $marketService->all(Argument::any(), Argument::any())->willReturn(null);

        $this
            ->shouldThrow(NotFoundException::class)
            ->duringGetDealsAction($paramFetcher);
    }
}
