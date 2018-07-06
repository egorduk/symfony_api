<?php

namespace spec\Btc\FrontendApiBundle\Controller;

use Btc\Component\Market\Service\OrderSubmission;
use Btc\CoreBundle\Entity\Market;
use Btc\CoreBundle\Entity\Order;
use Btc\CoreBundle\Entity\User;
use Btc\FrontendApiBundle\Controller\UserOrderController;
use Btc\FrontendApiBundle\Events\AccountActivityEvents;
use Btc\FrontendApiBundle\Exception\Rest\NoMarketException;
use Btc\FrontendApiBundle\Exception\Rest\NotFoundException;
use Btc\FrontendApiBundle\Repository\OrderRepository;
use Btc\FrontendApiBundle\Service\MarketService;
use Btc\FrontendApiBundle\Service\UserOrderService;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\View\View;
use FOS\RestBundle\View\ViewHandler;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\PreAuthenticatedToken;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;

class UserOrderControllerSpec extends ObjectBehavior
{
    const MARKET_ID_FAKE = 1;
    const PRICE_FAKE = 1.2;
    const AMOUNT_FAKE = 1;
    const ORDER_ID_FAKE = 1;

    public function let(
        ContainerInterface $container,
        ViewHandler $viewHandler,
        Response $response,
        Request $request,
        MarketService $marketService,
        Market $market,
        UserOrderService $userOrderService,
        TokenStorage $tokenStorage,
        User $user,
        PreAuthenticatedToken $preAuthenticatedToken,
        ParameterBag $parameterBag,
        UserOrderService $userOrderService,
        OrderRepository $orderRepository,
        OrderSubmission $orderSubmission
    ) {
        $this->setContainer($container);

        $container->get('rest.service.market')->willReturn($marketService);
        $container->get('rest.service.user_order')->willReturn($userOrderService);
        $container->get('rest.service.deal_submission')->willReturn($orderSubmission);
        $container->get('rest.repository.order')->willReturn($orderRepository);
        $container->get('security.token_storage')->willReturn($tokenStorage);
        $container->has('security.token_storage')->willReturn(true);
        $container->get('fos_rest.view_handler')->willReturn($viewHandler);

        $request->get('marketId', 0)->willReturn(self::MARKET_ID_FAKE);
        $request->get('amount')->willReturn(self::AMOUNT_FAKE);

        $request->request = $parameterBag;
        $parameterBag->all()->willReturn([]);

        $marketService->get(self::MARKET_ID_FAKE)->willReturn($market);

        $tokenStorage->getToken()->willReturn($preAuthenticatedToken);
        $preAuthenticatedToken->getUser()->willReturn($user);

        $viewHandler->handle(Argument::type(View::class))->willReturn($response);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(UserOrderController::class);
        $this->shouldHaveType(FOSRestController::class);
    }

    public function it_should_respond_to_post_order_limit_buy_open_action(Request $request, View $view, UserOrderService $userOrderService)
    {
        $request->get('askedUnitPrice', 0)->willReturn(self::PRICE_FAKE);
        $request->get('isBuy', 0)->willReturn(true);

        $userOrderService
            ->getOrderView(
                $request,
                Argument::type(Market::class),
                Argument::type(User::class),
                Argument::exact(Order::SIDE_BUY),
                Argument::exact(Order::TYPE_LIMIT),
                Argument::exact(AccountActivityEvents::LIMIT_BUY_ORDER)
            )
            ->willReturn($view)
            ->shouldBeCalled();

        $response = $this->postOrderOpenAction($request);
        $response->shouldHaveType(Response::class);
    }

    public function it_should_respond_to_post_order_limit_sell_open_action(Request $request, View $view, UserOrderService $userOrderService)
    {
        $request->get('askedUnitPrice', 0)->willReturn(self::PRICE_FAKE);
        $request->get('isBuy', 0)->willReturn(false);

        $userOrderService
            ->getOrderView(
                $request,
                Argument::type(Market::class),
                Argument::type(User::class),
                Argument::exact(Order::SIDE_SELL),
                Argument::exact(Order::TYPE_LIMIT),
                Argument::exact(AccountActivityEvents::LIMIT_SELL_ORDER)
            )
            ->willReturn($view)
            ->shouldBeCalled();

        $response = $this->postOrderOpenAction($request);
        $response->shouldHaveType(Response::class);
    }

    public function it_should_respond_to_post_order_market_buy_open_action(Request $request, View $view, UserOrderService $userOrderService)
    {
        $request->get('askedUnitPrice', 0)->willReturn(0);
        $request->get('isBuy', 0)->willReturn(true);

        $userOrderService
            ->getOrderView(
                $request,
                Argument::type(Market::class),
                Argument::type(User::class),
                Argument::exact(Order::SIDE_BUY),
                Argument::exact(Order::TYPE_MARKET),
                Argument::exact(AccountActivityEvents::MARKET_BUY_ORDER)
            )
            ->willReturn($view)
            ->shouldBeCalled();

        $response = $this->postOrderOpenAction($request);
        $response->shouldHaveType(Response::class);
    }

    public function it_should_respond_to_post_order_market_sell_open_action(Request $request, View $view, UserOrderService $userOrderService)
    {
        $request->get('askedUnitPrice', 0)->willReturn(0);
        $request->get('isBuy', 0)->willReturn(false);

        $userOrderService
            ->getOrderView(
                $request,
                Argument::type(Market::class),
                Argument::type(User::class),
                Argument::exact(Order::SIDE_SELL),
                Argument::exact(Order::TYPE_MARKET),
                Argument::exact(AccountActivityEvents::MARKET_SELL_ORDER)
            )
            ->willReturn($view)
            ->shouldBeCalled();

        $response = $this->postOrderOpenAction($request);
        $response->shouldHaveType(Response::class);
    }

    public function it_should_respond_to_set_order_status_close_action(OrderRepository $orderRepository, Order $order, OrderSubmission $orderSubmission)
    {
        $orderRepository->find(self::ORDER_ID_FAKE)->willReturn($order)->shouldBeCalled();

        $orderSubmission->cancelOrder($order)->willReturn(true)->shouldBeCalled();

        $response = $this->setOrderStatusCloseAction(self::ORDER_ID_FAKE);
        $response->shouldHaveType(Response::class);
    }

    public function it_should_respond_to_close_active_market_orders_action(OrderRepository $orderRepository, Order $order, UserOrderService $userOrderService)
    {
        $orderRepository->findBy(['status' => Order::STATUS_OPEN])->willReturn([$order])->shouldBeCalled();

        $userOrderService->patch($order, Argument::exact(null), Argument::exact(false))->willReturn($order)->shouldBeCalled();
        $userOrderService->flushAll()->shouldBeCalled();

        $response = $this->closeActiveMarketOrdersAction();
        $response->shouldHaveType(Response::class);
    }

    public function it_throws_an_exception_if_market_not_found_by_id(Request $request, MarketService $marketService)
    {
        $marketService->get(self::MARKET_ID_FAKE)->willReturn(null)->shouldBeCalled();

        $this
            ->shouldThrow(NoMarketException::class)
            ->duringPostOrderOpenAction($request);
    }

    public function it_throws_an_exception_if_order_not_found_by_id(OrderRepository $orderRepository)
    {
        $orderRepository->find(self::ORDER_ID_FAKE)->willReturn(null)->shouldBeCalled();

        $this
            ->shouldThrow(NotFoundException::class)
            ->duringSetOrderStatusCloseAction(self::ORDER_ID_FAKE);
    }

    public function it_throws_an_exception_if_order_not_found_by_status(OrderRepository $orderRepository)
    {
        $orderRepository->findBy(['status' => Order::STATUS_OPEN])->willReturn(null)->shouldBeCalled();

        $this
            ->shouldThrow(NotFoundException::class)
            ->duringCloseActiveMarketOrdersAction(self::ORDER_ID_FAKE);
    }
}
