<?php

namespace Btc\FrontendApiBundle\Service;

use Btc\Component\Market\Error\AlreadyCanceledError;
use Btc\Component\Market\Error\AlreadyCompletedError;
use Btc\Component\Market\Error\ErrorInterface;
use Btc\Component\Market\Error\IncorrectAskedPriceError;
use Btc\Component\Market\Error\InsufficientBalanceError;
use Btc\Component\Market\Error\MarketEmptyError;
use Btc\Component\Market\Error\MinOrderAmountError;
use Btc\Component\Market\Error\OrderValueTooLowError;
use Btc\Component\Market\Service\OrderSubmission;
use Btc\Component\Market\Service\FeeService;
use Btc\CoreBundle\Entity\Activity;
use Btc\CoreBundle\Entity\Market;
use Btc\CoreBundle\Entity\Order as OrderEntity;
use Btc\CoreBundle\Entity\RestEntityInterface;
use Btc\CoreBundle\Entity\User;
use Btc\FrontendApiBundle\Events\UserTradeActivityEvent;
use Btc\FrontendApiBundle\Exception\Rest\EmptyMarketException;
use Btc\FrontendApiBundle\Exception\Rest\InvalidFormException;
use Btc\FrontendApiBundle\Exception\Rest\LowOrderValueException;
use Btc\FrontendApiBundle\Exception\Rest\MinOrderQuantityException;
use Btc\FrontendApiBundle\Exception\Rest\NotEnoughMoneyException;
use Btc\FrontendApiBundle\Exception\Rest\NotValidDataException;
use Btc\FrontendApiBundle\Exception\Rest\OrderAlreadyCancelledException;
use Btc\FrontendApiBundle\Exception\Rest\OrderAlreadyCompletedException;
use Btc\FrontendApiBundle\Exception\Rest\OrderInvalidPriceException;
use Btc\FrontendApiBundle\Exception\RestException;
use Btc\FrontendApiBundle\Form\LimitOrderType;
use Btc\FrontendApiBundle\Form\MarketOrderType;
use Btc\FrontendApiBundle\Form\StopLimitOrderType;
use Btc\FrontendApiBundle\Form\StopMarketOrderType;
use Btc\FrontendApiBundle\Repository\WalletRepository;
use Doctrine\ORM\EntityManager;
use FOS\RestBundle\View\View;
use JMS\Serializer\SerializationContext;
use JMS\Serializer\SerializerBuilder;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class UserOrderService extends RestService
{
    private $em;
    private $formFactory;
    private $entityClass;
    private $repository;
    private $orderSubmission;
    private $ed;
    private $marketService;
    private $walletService;
    private $walletRepository;
    private $feeService;

    public function __construct(
        EntityManager $em,
        FormFactoryInterface $formFactory,
        OrderSubmission $orderSubmission,
        EventDispatcherInterface $ed,
        MarketService $marketService,
        WalletRepository $walletRepository,
        WalletService $walletService,
        FeeService $feeService,
        $entityClass
    ) {
        $this->em = $em;
        $this->entityClass = $entityClass;
        $this->repository = $this->em->getRepository($this->entityClass);
        $this->formFactory = $formFactory;
        $this->orderSubmission = $orderSubmission;
        $this->ed = $ed;
        $this->marketService = $marketService;
        $this->walletRepository = $walletRepository;
        $this->walletService = $walletService;
        $this->feeService = $feeService;

        parent::__construct($em, $entityClass);
    }

    /**
     * Creates a new order.
     *
     * @param array $parameters
     *
     * @return RestEntityInterface | RestException | string
     */
    public function post(array $parameters)
    {
        $entity = $this->createEntity();

        return $this->processForm($entity, $parameters);
    }

    /**
     * @param RestEntityInterface $obj
     * @param array               $parameters
     * @param string              $method
     *
     * @return RestEntityInterface | RestException | string
     */
    private function processForm(RestEntityInterface $obj, array $parameters, $method = 'POST')
    {
        $side = $parameters['side'];
        $type = $parameters['type'];

        $formType = OrderEntity::TYPE_LIMIT === $type ?
            new LimitOrderType($side, get_class($obj)) :
            (OrderEntity::TYPE_MARKET === $type ?
                new MarketOrderType($side, get_class($obj)) :
                new StopLimitOrderType($side, get_class($obj)));

        $form = $this->formFactory
            ->create($formType, null, [
                'method' => $method,
                'validation_groups' => OrderEntity::TYPE_LIMIT === $parameters['type'] ?
                    OrderEntity::TYPE_LIMIT_STR :
                    (OrderEntity::TYPE_MARKET === $parameters['type'] ?
                        OrderEntity::TYPE_MARKET_STR :
                        OrderEntity::TYPE_STOP_LIMIT_STR),
            ]);

        $form->submit($parameters);

        if ($form->isValid()) {
            $market = $parameters['market'];
            $user = $parameters['user'];
            $request = $parameters['request'];
            $events = $parameters['events'];

            $order = $form->getData();

            $feePercents = $this->getFeesPercent($user, $market, $side);
            $order->setFeePercent($feePercents[1]);
            $order->setSide($side);
            $order->setType($type);

            $this->setWallets($order, $market, $user);

            $response = $this->orderSubmission->submitOrder($order);

            if (!$response instanceof ErrorInterface) {
                $this->ed->dispatch(
                    $events,
                    new UserTradeActivityEvent($user, $request, $response)
                );
            }

            return $response;
        }

        throw new NotValidDataException();
    }

    public function setWallets(OrderEntity $order, Market $market, User $user)
    {
        $firstWallet = $this->walletRepository->findOneForUserAndCurrency($user, $market->getWithCurrency());
        $secondWallet = $this->walletRepository->findOneForUserAndCurrency($user, $market->getCurrency());

        if (OrderEntity::SIDE_SELL === $order->getSide()) {
            $order->setInWallet($firstWallet);
            $order->setOutWallet($secondWallet);
        } else {
            $order->setInWallet($secondWallet);
            $order->setOutWallet($firstWallet);
        }

        $order->setMarket($market);

        return $order;
    }

    /**
     * @param User   $user
     * @param Market $market
     * @param string $side
     *
     * @return array
     */
    private function getFeesPercent(User $user, Market $market, $side)
    {
        $feeSet = $this->feeService->getFeeSet($user, $market);

        return OrderEntity::SIDE_SELL === $side ?
            $feeSet->getSellFeeByMarket($market->getId()) :
            $feeSet->getBuyFeeByMarket($market->getId());
    }

    public function updateStatus(RestEntityInterface $obj, $status = '')
    {
        $obj->setStatus($status);

        return parent::patch($obj);
    }

    public function flushAll()
    {
        $this->repository->flushAll();
    }

    /**
     * Posts and gets result as view.
     *
     * @param Request $request
     * @param Market $market
     * @param User $user
     * @param string $side
     * @param string $type
     * @param int $stopPrice
     * @param string $events
     *
     * @return View
     */
    public function getOrderView(Request $request, Market $market, User $user, $side, $type, $stopPrice = 0, $events)
    {
        $response = $this->post(
            array_merge($request->request->all(), [
                'side' => $side,
                'type' => $type,
                'events' => $events,
                'market' => $market,
                'user' => $user,
                'request' => $request,
                'stopPrice' => $stopPrice,
            ])
        );

        if ($response instanceof ErrorInterface) {
            $this->convertFormErrorToException($response);
        }

        $isOrder = $response instanceof RestEntityInterface;
        $dataView = $isOrder ? [$response] : ['error' => $response];

        return new View($dataView, $isOrder ? Response::HTTP_CREATED : Response::HTTP_BAD_REQUEST);
    }

    /**
     * @param ErrorInterface $error
     */
    protected function convertFormErrorToException(ErrorInterface $error)
    {
        if ($error instanceof InsufficientBalanceError) {
            throw new NotEnoughMoneyException();
        }

        if ($error instanceof MinOrderAmountError) {
            throw new MinOrderQuantityException();
        }

        if ($error instanceof MarketEmptyError) {
            throw new EmptyMarketException();
        }

        if ($error instanceof OrderValueTooLowError) {
            throw new LowOrderValueException();
        }

        if ($error instanceof IncorrectAskedPriceError) {
            throw new OrderInvalidPriceException();
        }

        if ($error instanceof AlreadyCompletedError) {
            throw new OrderAlreadyCompletedException();
        }

        if ($error instanceof AlreadyCanceledError) {
            throw new OrderAlreadyCancelledException();
        }
    }

    public function findOpenLimitOrdersWithinMarket(User $user, Market $market, $offset = 0, $limit = null, $sort = 'desc')
    {
        $wallets = $this->walletRepository->findAllUserWalletsWithinMarket($user, $market);

        return $this->repository->getOpenLimitOrders($wallets, $market, $sort, $offset, $limit);
    }

    public function findCompletedAndCanceledOrdersWithinMarket(User $user, Market $market, $offset = 0, $limit = null, $sort = 'desc')
    {
        $wallets = $this->walletRepository->findAllUserWalletsWithinMarket($user, $market);

        return $this->repository->getCompletedAndCanceledOrders($wallets, $market, $sort, $offset, $limit);
    }

    public function findNotClosedOrder($id)
    {
        return $this->repository->getNotClosedOrder($id);
    }
}
