<?php

namespace spec\Btc\FrontendApiBundle\Service;

use Btc\Component\Market\Model\FeeSet;
use Btc\Component\Market\Service\FeeService;
use Btc\Component\Market\Service\OrderSubmission;
use Btc\CoreBundle\Entity\Currency;
use Btc\CoreBundle\Entity\Market;
use Btc\CoreBundle\Entity\Order;
use Btc\CoreBundle\Entity\RestEntityInterface;
use Btc\CoreBundle\Entity\User;
use Btc\CoreBundle\Entity\Wallet;
use Btc\FrontendApiBundle\Exception\Rest\NotValidDataException;
use Btc\FrontendApiBundle\Form\LimitOrderType;
use Btc\FrontendApiBundle\Form\MarketOrderType;
use Btc\FrontendApiBundle\Repository\OrderRepository;
use Btc\FrontendApiBundle\Repository\WalletRepository;
use Btc\FrontendApiBundle\Service\MarketService;
use Btc\FrontendApiBundle\Service\RestService;
use Btc\FrontendApiBundle\Service\UserOrderService;
use Btc\FrontendApiBundle\Service\WalletService;
use Doctrine\ORM\EntityManager;
use FOS\RestBundle\View\View;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;

class UserOrderServiceSpec extends ObjectBehavior
{
    const ID_FAKE = 1;
    const SLUG_FAKE = 'slug';

    public function let(
        EntityManager $em,
        FormFactoryInterface $formFactory,
        OrderSubmission $orderSubmission,
        EventDispatcherInterface $ed,
        MarketService $marketService,
        WalletRepository $walletRepository,
        WalletService $walletService,
        FeeService $feeService,
        OrderRepository $orderRepository,
        Request $request,
        ParameterBag $parameterBag,
        Market $market,
        Currency $currency,
        Wallet $wallet,
        FeeService $feeService,
        FeeSet $feeSet,
        Order $order
    ) {
        $em->getRepository($order)->willReturn($orderRepository);

        $request->request = $parameterBag;
        $parameterBag->all()->willReturn([]);

        $market->getWithCurrency()->willReturn($currency);
        $market->getCurrency()->willReturn($currency);
        $market->getSlug()->willReturn(self::SLUG_FAKE);
        $market->getId()->willReturn(self::ID_FAKE);

        $wallet->getId()->willReturn(self::ID_FAKE);

        $feeService->getFeeSet(Argument::type(User::class), Argument::type(Market::class))->willReturn($feeSet);

        $this->beConstructedWith($em, $formFactory, $orderSubmission, $ed, $marketService, $walletRepository, $walletService, $feeService, $order);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(UserOrderService::class);
        $this->shouldHaveType(RestService::class);
    }

    public function it_should_create_entity()
    {
        $this->createEntity()->shouldHaveType(\Btc\Component\Market\Model\Order::class);
    }

    public function it_should_update_status(Order $order, OrderRepository $orderRepository)
    {
        $orderRepository->save($order, true)->willReturn(RestEntityInterface::class)->shouldBeCalled();

        $this->updateStatus($order, Order::STATUS_OPEN)->shouldBe(RestEntityInterface::class);
    }

    public function it_should_flush_all(OrderRepository $orderRepository)
    {
        $orderRepository->flushAll()->shouldBeCalled();

        $this->flushAll();
    }

    public function it_should_open_limit_order_and_get_order_view(
        FormFactoryInterface $formFactory,
        FormInterface $form,
        User $user,
        Market $market,
        Request $request,
        \Btc\Component\Market\Model\Order $order,
        WalletRepository $walletRepository,
        Wallet $wallet
    ) {
        $formFactory->create(Argument::type(LimitOrderType::class), null, Argument::type('array'))->willReturn($form);

        $form->submit([
            'side' => Argument::any(),
            'type' => Order::TYPE_LIMIT,
            'events' => Argument::any(),
            'market' => $market,
            'user' => $user,
            'request' => $request
        ])->shouldBeCalled();

        $form->isValid()->willReturn(true);
        $form->getData()->willReturn($order);

        $walletRepository
            ->findOneForUserAndCurrency(Argument::type(User::class), Argument::type(Currency::class))
            ->willReturn($wallet)
            ->shouldBeCalled();

        $this->getOrderView($request, $market, $user, Argument::any(), Order::TYPE_LIMIT, Argument::any())->shouldHaveType(View::class);
    }

    public function it_should_open_market_order_and_get_order_view(
        FormFactoryInterface $formFactory,
        FormInterface $form,
        User $user,
        Market $market,
        Request $request,
        \Btc\Component\Market\Model\Order $order,
        WalletRepository $walletRepository,
        Wallet $wallet
    ) {
        $formFactory->create(Argument::type(MarketOrderType::class), null, Argument::type('array'))->willReturn($form);

        $form->submit([
            'side' => Argument::any(),
            'type' => Order::TYPE_MARKET,
            'events' => Argument::any(),
            'market' => $market,
            'user' => $user,
            'request' => $request
        ])->shouldBeCalled();

        $form->isValid()->willReturn(true);
        $form->getData()->willReturn($order);

        $walletRepository
            ->findOneForUserAndCurrency(Argument::type(User::class), Argument::type(Currency::class))
            ->willReturn($wallet)
            ->shouldBeCalled();

        $this->getOrderView($request, $market, $user, Argument::any(), Order::TYPE_MARKET, Argument::any())->shouldHaveType(View::class);
    }

    public function it_should_throw_not_valid_data_exception_for_open_order_and_get_order_view(
        User $user,
        Market $market,
        Request $request,
        FormFactoryInterface $formFactory,
        FormInterface $form
    ) {
        $formFactory->create(Argument::type(MarketOrderType::class), null, Argument::type('array'))->willReturn($form);

        $form->submit([
            'side' => Argument::any(),
            'type' => Argument::any(),
            'events' => Argument::any(),
            'market' => $market,
            'user' => $user,
            'request' => $request
        ])->shouldBeCalled();

        $form->isValid()->willReturn(false);

        $this
            ->shouldThrow(NotValidDataException::class)
            ->duringGetOrderView($request, $market, $user, Argument::any(), Argument::any(), Argument::any());
    }
}
