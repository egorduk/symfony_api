<?php

namespace spec\Btc\FrontendApiBundle\Controller;

use Btc\Component\Market\Service\OrderBookService;
use Btc\FrontendApiBundle\Controller\OrderBookController;
use Btc\FrontendApiBundle\Exception\Rest\NotFoundException;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Request\ParamFetcher;
use FOS\RestBundle\View\View;
use FOS\RestBundle\View\ViewHandler;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;

class OrderBookControllerSpec extends ObjectBehavior
{
    public function let(
        ContainerInterface $container,
        ViewHandler $viewHandler,
        OrderBookService $orderBookService
    ) {
        $this->setContainer($container);

        $container->get('rest.service.order_book')->willReturn($orderBookService);
        $container->get('fos_rest.view_handler')->willReturn($viewHandler);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(OrderBookController::class);
        $this->shouldHaveType(FOSRestController::class);
    }

    public function it_should_respond_to_get_order_books_by_market_slug_action(
        ParamFetcher $paramFetcher,
        Response $response,
        ViewHandler $viewHandler,
        OrderBookService $orderBookService
    ) {
        $orderBookService->getBuyDeals(Argument::any(), Argument::any(), Argument::any())->willReturn(['deals']);
        $orderBookService->getSellDeals(Argument::any(), Argument::any(), Argument::any())->willReturn(['deals']);

        $viewHandler->handle(Argument::type(View::class))->willReturn($response);

        $response = $this->getOrderBooksByMarketSlugAction($paramFetcher, Argument::any());
        $response->shouldHaveType(Response::class);
    }

    public function it_throws_an_exception_if_data_not_found(OrderBookService $orderBookService, ParamFetcher $paramFetcher)
    {
        $orderBookService->getBuyDeals(Argument::any(), Argument::any(), Argument::any())->willReturn([]);
        $orderBookService->getSellDeals(Argument::any(), Argument::any(), Argument::any())->willReturn([]);

        $this
            ->shouldThrow(NotFoundException::class)
            ->duringGetOrderBooksByMarketSlugAction($paramFetcher, Argument::any());
    }
}
