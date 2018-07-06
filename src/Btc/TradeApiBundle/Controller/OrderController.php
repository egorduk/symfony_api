<?php

namespace Btc\TradeApiBundle\Controller;

use Btc\Component\Market\Error\ErrorInterface;
use Btc\CoreBundle\Entity\Market;
use Btc\CoreBundle\Entity\Order;
use Btc\CoreBundle\Entity\User;
use Btc\FrontendApiBundle\Exception\Rest\AccessDeniedException;
use Btc\FrontendApiBundle\Exception\Rest\NotFoundException;
use Btc\FrontendApiBundle\Exception\Rest\NotValidDataException;
use Btc\FrontendApiBundle\Exception\Rest\UnknownErrorException;
use Btc\TradeApiBundle\Presenter\DealsList;
use Btc\TradeApiBundle\Presenter\OrderPresenter;
use FOS\RestBundle\Controller\Annotations;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Request\ParamFetcherInterface;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class OrderController extends FOSRestController
{
    /**
     * Gets open orders.
     *
     * ### Request URL example ###
     * GET /api/trade/v1/orders/open?market=btc-usd&sort=asc&offset=10
     * ### Success response example ###
     *     {
     *       "buy": [
     *          {
     *              "id": "",
     *              "type": "",
     *              "amount": "",
     *              "current_amount": "",
     *              "price": "",
     *              "timestamp": ""
     *          }
     *      ],
     *      "sell": [
     *          {
     *              "id": "",
     *              "type": "",
     *              "amount": "",
     *              "current_amount": "",
     *              "price": "",
     *              "timestamp": ""
     *          }
     *      ]
     *     }
     * ### Error response example ###
     *      {
     *       "status": 400,
     *       "error": "INCORRECT_DATA"
     *     },
     *     {
     *       "status": 404,
     *       "error": "NOT_FOUND"
     *     }
     *
     * @ApiDoc(
     *   resource = true,
     *   description = "Gets open orders",
     *   statusCodes = {
     *     Response::HTTP_OK = "Returned when successful",
     *     Response::HTTP_BAD_REQUEST = "Returned when inputted incorrect data",
     *     Response::HTTP_NOT_FOUND = "Returned when the data are not found"
     *   },
     *   section = "Trade",
     *   authentication = true
     * )
     *
     * @Annotations\QueryParam(name="market", requirements="\S+", description="Market code name.")
     * @Annotations\QueryParam(name="offset", requirements="\d+", strict=true, default="0", description="Grab orders from specific offset.")
     * @Annotations\QueryParam(name="sort", requirements="(asc|desc)", strict=true, default="desc", description="Sort order.")
     * @Annotations\RequestParam(name="nonce", requirements="\S+", description="Nonce.")
     *
     * @param ParamFetcherInterface $paramFetcher
     *
     * @return Response
     */
    public function getOrdersOpenAction(ParamFetcherInterface $paramFetcher)
    {
        $market = $paramFetcher->get('market');
        $offset = $paramFetcher->get('offset');
        $sort = $paramFetcher->get('sort');

        if (!$market = $this->get('rest.service.market')->getOneBy(['slug' => $market])) {
            throw new NotFoundException();
        }

        $user = $this->getUser();

        $orders = $this->get('rest.service.user_order')
            ->findOpenLimitOrdersWithinMarket($user, $market, $offset, 10000, $sort);

        $bids = array_values(array_filter($orders, function(Order $o) {
            return $o->getSide() === Order::SIDE_BUY;
        }));

        $asks = array_values(array_filter($orders, function(Order $o) {
            return $o->getSide() === Order::SIDE_SELL;
        }));

        $data = new DealsList($bids, $asks);

        return $this->handleView(
            $this->view($data->presentAsJson(), Response::HTTP_OK)
        );
    }

    /**
     * Gets completed and canceled orders.
     *
     * ### Request URL example ###
     * GET /api/trade/v1/orders?market=btc-usd&sort=asc&offset=10
     * ### Success response example ###
     *     {
     *       "buy": [
     *          {
     *              "id": "",
     *              "type": "",
     *              "amount": "",
     *              "current_amount": "",
     *              "price": "",
     *              "timestamp": ""
     *          }
     *      ],
     *      "sell": [
     *          {
     *              "id": "",
     *              "type": "",
     *              "amount": "",
     *              "current_amount": "",
     *              "price": "",
     *              "timestamp": ""
     *          }
     *      ]
     *     }
     * ### Error response example ###
     *      {
     *       "status": 400,
     *       "error": "INCORRECT_DATA"
     *     },
     *     {
     *       "status": 404,
     *       "error": "NOT_FOUND"
     *     }
     *
     * @ApiDoc(
     *   resource = true,
     *   description = "Gets completed and canceled orders",
     *   statusCodes = {
     *     Response::HTTP_OK = "Returned when successful",
     *     Response::HTTP_BAD_REQUEST = "Returned when inputted incorrect data",
     *     Response::HTTP_NOT_FOUND = "Returned when the data are not found"
     *   },
     *   section = "Trade",
     *   authentication = true
     * )
     *
     * @Annotations\QueryParam(name="market", requirements="\S+", description="Market code name.")
     * @Annotations\QueryParam(name="offset", requirements="\d+", strict=true, default="0", description="Grab orders from specific offset.")
     * @Annotations\QueryParam(name="sort", requirements="(asc|desc)", strict=true, default="desc", description="Sort order.")
     * @Annotations\RequestParam(name="nonce", requirements="\S+", description="Nonce.")
     *
     * @param ParamFetcherInterface $paramFetcher
     *
     * @return Response
     */
    public function getOrdersAction(ParamFetcherInterface $paramFetcher)
    {
        $market = $paramFetcher->get('market');
        $offset = $paramFetcher->get('offset');
        $sort = $paramFetcher->get('sort');

        if (!$market = $this->get('rest.service.market')->getOneBy(['slug' => $market])) {
            throw new NotFoundException();
        }

        $user = $this->getUser();

        $orders = $this->get('rest.service.user_order')
            ->findCompletedAndCanceledOrdersWithinMarket($user, $market, $offset, 50, $sort);

        $bids = array_values(array_filter($orders, function(Order $o) {
            return $o->getSide() === Order::SIDE_BUY;
        }));

        $asks = array_values(array_filter($orders, function(Order $o) {
            return $o->getSide() === Order::SIDE_SELL;
        }));

        $data = new DealsList($bids, $asks);

        return $this->handleView(
            $this->view($data->presentAsJson(), Response::HTTP_OK)
        );
    }

    /**
     * Gets buy order.
     *
     * ### Request URL example ###
     * GET /api/trade/v1/orders/buy?id=1
     * ### Success response example ###
     *     {
     *        "id": 0,
     *        "type": "",
     *        "side": "",
     *        "market": "",
     *        "amount": "",
     *        "current_amount": "",
     *        "price": "",
     *        "status": "",
     *        "timestamp": 0,
     *        "fee_taken": "",
     *        "fee_fixed": "",
     *        "fee_percent": "",
     *        "transactions": [
     *          {
     *              "id": 0,
     *              "order_id": 0,
     *              "status": "",
     *              "amount": "",
     *              "price": "",
     *              "fee": "",
     *              "timestamp": 0
     *          }
     *       ]
     *     }
     * ### Error response example ###
     *     {
     *       "status": 404,
     *       "error": "NOT_FOUND"
     *     }
     *
     * @ApiDoc(
     *   resource = true,
     *   description = "Gets buy order",
     *   statusCodes = {
     *     Response::HTTP_OK = "Returned when successful",
     *     Response::HTTP_NOT_FOUND = "Returned when the data are not found"
     *   },
     *   section = "Trade"
     * )
     *
     * @Annotations\Get("/orders/buy/{id}", requirements = { "id" = "\d+" })
     *
     * @Annotations\RequestParam(name="nonce", requirements="\S+", description="Nonce.")
     *
     * @param int $id
     *
     * @return Response
     *
     * @throws NotFoundHttpException when data do not exist
     */
    public function getOrdersBuyAction($id)
    {
        if (!$order = $this->get('rest.service.user_order')->getOneBy(['id' => $id, 'side' => Order::SIDE_BUY])) {
            throw new NotFoundException();
        }

        $data = new OrderPresenter($order);

        return $this->handleView(
            $this->view($data->presentAsJson(), Response::HTTP_OK)
        );
    }

    /**
     * Gets sell order.
     *
     * ### Request URL example ###
     * GET /api/trade/v1/orders/sell?id=1
     * ### Success response example ###
     *     {
     *        "id": 0,
     *        "type": "",
     *        "side": "",
     *        "market": "",
     *        "amount": "",
     *        "current_amount": "",
     *        "price": "",
     *        "status": "",
     *        "timestamp": 0,
     *        "fee_taken": "",
     *        "fee_fixed": "",
     *        "fee_percent": "",
     *        "transactions": [
     *          {
     *              "id": 0,
     *              "order_id": 0,
     *              "status": "",
     *              "amount": "",
     *              "price": "",
     *              "fee": "",
     *              "timestamp": 0
     *          }
     *       ]
     *     }
     * ### Error response example ###
     *     {
     *       "status": 404,
     *       "error": "NOT_FOUND"
     *     }
     *
     * @ApiDoc(
     *   resource = true,
     *   description = "Gets sell order",
     *   statusCodes = {
     *     Response::HTTP_OK = "Returned when successful",
     *     Response::HTTP_NOT_FOUND = "Returned when the data are not found"
     *   },
     *   section = "Trade"
     * )
     *
     * @Annotations\Get("/orders/sell/{id}", requirements = { "id" = "\d+" })
     *
     * @Annotations\RequestParam(name="nonce", requirements="\S+", description="Nonce.")
     *
     * @param int $id
     *
     * @return Response
     *
     * @throws NotFoundHttpException when data do not exist
     */
    public function getOrdersSellAction($id)
    {
        if (!$order = $this->get('rest.service.user_order')->getOneBy(['id' => $id, 'side' => Order::SIDE_SELL])) {
            throw new NotFoundException();
        }

        $data = new OrderPresenter($order);

        return $this->handleView(
            $this->view($data->presentAsJson(), Response::HTTP_OK)
        );
    }

    /**
     * Gets order.
     *
     * ### Request URL example ###
     * GET /api/trade/v1/orders?id=1
     * ### Success response example ###
     *     {
     *        "id": 0,
     *        "type": "",
     *        "side": "",
     *        "market": "",
     *        "amount": "",
     *        "current_amount": "",
     *        "price": "",
     *        "status": "",
     *        "timestamp": 0,
     *        "fee_taken": "",
     *        "fee_fixed": "",
     *        "fee_percent": "",
     *        "transactions": [
     *          {
     *              "id": 0,
     *              "order_id": 0,
     *              "status": "",
     *              "amount": "",
     *              "price": "",
     *              "fee": "",
     *              "timestamp": 0
     *          }
     *       ]
     *     }
     * ### Error response example ###
     *     {
     *       "status": 404,
     *       "error": "NOT_FOUND"
     *     }
     *
     * @ApiDoc(
     *   resource = true,
     *   description = "Gets order",
     *   statusCodes = {
     *     Response::HTTP_OK = "Returned when successful",
     *     Response::HTTP_NOT_FOUND = "Returned when the data are not found"
     *   },
     *   section = "Trade"
     * )
     *
     * @Annotations\Get("/orders/{id}", requirements = { "id" = "\d+" })
     *
     * @Annotations\RequestParam(name="nonce", requirements="\S+", description="Nonce.")
     *
     * @param int $id
     *
     * @return Response
     *
     * @throws NotFoundHttpException when data do not exist
     */
    public function getOrderAction($id)
    {
        if (!$order = $this->get('rest.service.user_order')->getOneBy(['id' => $id])) {
            throw new NotFoundException();
        }

        $data = new OrderPresenter($order);

        return $this->handleView(
            $this->view($data->presentAsJson(), Response::HTTP_OK)
        );
    }

    /**
     * Creates a new buy order.
     *
     * ### Request URL example ###
    POST /api/v1/orders/buy
    body: {"market":"btc-usd","type":"limit","price":"100","amount":"5"}
     * ### Success response example ###
     *     {
     *        "id": 0,
     *        "type": "",
     *        "side": "",
     *        "market": "",
     *        "amount": "",
     *        "current_amount": "",
     *        "price": "",
     *        "status": "",
     *        "timestamp": 0,
     *        "fee_taken": "",
     *        "fee_fixed": "",
     *        "fee_percent": "",
     *        "transactions": [
     *          {
     *              "id": 0,
     *              "order_id": 0,
     *              "status": "",
     *              "amount": "",
     *              "price": "",
     *              "fee": "",
     *              "timestamp": 0
     *          }
     *       ]
     *     }
     * ### Error response example ###
     *     {
     *       "status": 400,
     *       "error": "VALIDATION_ERROR"
     *     }
     *     {
     *       "status": 400,
     *       "error": "INCORRECT_DATA"
     *     }
     *
     * @ApiDoc(
     *   resource = true,
     *   description = "Creates a new buy order",
     *   statusCodes = {
     *     Response::HTTP_CREATED = "Returned when successful",
     *     Response::HTTP_BAD_REQUEST = "Returned when errors",
     *     Response::HTTP_NOT_FOUND = "Returned when the data are not found",
     *   },
     *   section = "Trade",
     *   authentication = true
     * )
     *
     * @Annotations\Post("/orders/buy")
     *
     * @Annotations\RequestParam(name="market", requirements="\S+", description="Market code name.")
     * @Annotations\RequestParam(name="type", requirements="(limit|market)", default="limit", description="Order type.")
     * @Annotations\RequestParam(name="price", requirements="[0-9\.]+", default="0", description="Asked price per unit. Required when submitting 'limit' type order.")
     * @Annotations\RequestParam(name="amount", requirements="[0-9\.]+", default="0", description="Amount to buy.")
     * @Annotations\RequestParam(name="nonce", requirements="\S+", description="Nonce.")
     *
     * @param Request $request
     *
     * @return Response
     *
     * @throws NotFoundException
     */
    public function postOrdersBuyAction(Request $request)
    {
        $market = $request->get('market');
        $type = $request->get('type');
        $price = $request->get('price');
        $amount = $request->get('amount');

        if (!$market = $this->get('rest.service.market')->getOneBy(['slug' => $market])) {
            throw new NotFoundException();
        }

        $user = $this->getUser();

        $order = $this->prepareOrder($market, $user, $type, Order::SIDE_BUY, $price, $amount);

        $violations = $this->get('validator')->validate($order, [ucfirst($order->getType())]);

        if ($violations->count()) {
            throw new NotValidDataException();
        }

        $order = $this->setFeesForBuyDeal($order, $user, $market);

        $isError = $this->get('rest.service.deal_submission')->submitOrder($order);

        if ($isError instanceof ErrorInterface) {
            throw new UnknownErrorException();
        }

        $order = $this->get('rest.repository.order')->find($order->getId());

        $data = new OrderPresenter($order);

        return $this->handleView(
            $this->view($data->presentAsJson(), Response::HTTP_OK)
        );
    }

    private function prepareOrder(Market $market, User $user, $type, $side, $price, $amount)
    {
        $order = new Order();
        $order->setSide($side);
        $order->setType($type === strtolower(Order::TYPE_MARKET_STR) ? Order::TYPE_MARKET : Order::TYPE_LIMIT);
        $order->setMarket($market);
        $order->setAmount($amount);
        $order->setAskedUnitPrice((double)$price);
        $order = $this->get('rest.service.user_order')->setWallets($order, $market, $user);

        return $order;
    }

    private function setFeesForBuyDeal(Order $order, User $user, Market $market)
    {
        $feeService = $this->get('rest.service.fee_service');
        $feeSet = $feeService->getFeeSet($user, $market);
        list(, $percent) = $feeSet->getBuyFeeByMarket($order->getMarketId());
        $order->setFeePercent($percent);

        return $order;
    }

    private function setFeesForSellDeal(Order $order, User $user, Market $market)
    {
        $feeService = $this->get('rest.service.fee_service');
        $feeSet = $feeService->getFeeSet($user, $market);
        list(, $percent) = $feeSet->getSellFeeByMarket($order->getMarketId());
        $order->setFeePercent($percent);

        return $order;
    }

    /**
     * Creates a new sell order.
     *
     * ### Request URL example ###
    POST /api/v1/orders/sell
    body: {"market":"btc-usd","type":"limit","price":"100","amount":"5"}
     * ### Success response example ###
     *     {
     *        "id": 0,
     *        "type": "",
     *        "side": "",
     *        "market": "",
     *        "amount": "",
     *        "current_amount": "",
     *        "price": "",
     *        "status": "",
     *        "timestamp": 0,
     *        "fee_taken": "",
     *        "fee_fixed": "",
     *        "fee_percent": "",
     *        "transactions": [
     *          {
     *              "id": 0,
     *              "order_id": 0,
     *              "status": "",
     *              "amount": "",
     *              "price": "",
     *              "fee": "",
     *              "timestamp": 0
     *          }
     *       ]
     *     }
     * ### Error response example ###
     *     {
     *       "status": 400,
     *       "error": "VALIDATION_ERROR"
     *     }
     *     {
     *       "status": 400,
     *       "error": "INCORRECT_DATA"
     *     }
     *
     * @ApiDoc(
     *   resource = true,
     *   description = "Creates a new sell order",
     *   statusCodes = {
     *     Response::HTTP_CREATED = "Returned when successful",
     *     Response::HTTP_BAD_REQUEST = "Returned when errors",
     *     Response::HTTP_NOT_FOUND = "Returned when the data are not found",
     *   },
     *   section = "Trade",
     *   authentication = true
     * )
     *
     * @Annotations\Post("/orders/sell")
     *
     * @Annotations\RequestParam(name="market", requirements="\S+", description="Market code name.")
     * @Annotations\RequestParam(name="type", requirements="(limit|market)", default="limit", description="Order type.")
     * @Annotations\RequestParam(name="price", requirements="[0-9\.]+", default="0", description="Asked price per unit. Required when submitting 'limit' type order.")
     * @Annotations\RequestParam(name="amount", requirements="[0-9\.]+", default="0", description="Amount to buy.")
     * @Annotations\RequestParam(name="nonce", requirements="\S+", description="Nonce.")
     *
     * @param Request $request
     *
     * @return Response
     *
     * @throws NotFoundException
     */
    public function postOrdersSellAction(Request $request)
    {
        $market = $request->get('market');
        $type = $request->get('type');
        $price = $request->get('price');
        $amount = $request->get('amount');

        if (!$market = $this->get('rest.service.market')->getOneBy(['slug' => $market])) {
            throw new NotFoundException();
        }

        $user = $this->getUser();

        $order = $this->prepareOrder($market, $user, $type, Order::SIDE_SELL, $price, $amount);

        $violations = $this->get('validator')->validate($order, [ucfirst($order->getType())]);

        if ($violations->count()) {
            throw new NotValidDataException();
        }

        $order = $this->setFeesForSellDeal($order, $user, $market);

        $isError = $this->get('rest.service.deal_submission')->submitOrder($order);

        if ($isError instanceof ErrorInterface) {
            throw new UnknownErrorException();
        }

        $order = $this->get('rest.repository.order')->find($order->getId());

        $data = new OrderPresenter($order);

        return $this->handleView(
            $this->view($data->presentAsJson(), Response::HTTP_OK)
        );
    }

    /**
     * Cancels buy order.
     *
     * ### Request URL example ###
    DELETE /api/v1/orders/buy/1
     * ### Success response example ###
     *     {
     *        "id": 0,
     *        "type": "",
     *        "side": "",
     *        "market": "",
     *        "amount": "",
     *        "current_amount": "",
     *        "price": "",
     *        "status": "",
     *        "timestamp": 0,
     *        "fee_taken": "",
     *        "fee_fixed": "",
     *        "fee_percent": "",
     *        "transactions": [
     *          {
     *              "id": 0,
     *              "order_id": 0,
     *              "status": "",
     *              "amount": "",
     *              "price": "",
     *              "fee": "",
     *              "timestamp": 0
     *          }
     *       ]
     *     }
     * ### Error response example ###
     *     {
     *       "status": 400,
     *       "error": "VALIDATION_ERROR"
     *     }
     *     {
     *       "status": 400,
     *       "error": "INCORRECT_DATA"
     *     }
     *
     * @ApiDoc(
     *   resource = true,
     *   description = "Cancels buy order",
     *   statusCodes = {
     *     Response::HTTP_CREATED = "Returned when successful",
     *     Response::HTTP_BAD_REQUEST = "Returned when errors",
     *     Response::HTTP_NOT_FOUND = "Returned when the data are not found",
     *   },
     *   section = "Trade",
     *   authentication = true
     * )
     *
     * @Annotations\Delete("/orders/buy/{id}", requirements = { "id" = "\d+" })
     *
     * @Annotations\RequestParam(name="nonce", requirements="\S+", description="Nonce.")
     *
     * @param int $id
     *
     * @return Response
     *
     * @throws NotFoundException
     */
    public function deleteOrdersBuyAction($id)
    {
        if (!$order = $this->get('rest.service.user_order')->getOneBy(['id' => $id, 'side' => Order::SIDE_BUY])) {
            throw new NotFoundException();
        }

        $isError = $this->get('rest.service.deal_submission')->cancelOrder($order);

        if ($isError instanceof ErrorInterface) {
            throw new UnknownErrorException();
        }

        $order = $this->get('rest.repository.order')->find($order->getId());

        $data = new OrderPresenter($order);

        return $this->handleView(
            $this->view($data->presentAsJson(), Response::HTTP_OK)
        );
    }

    /**
     * Cancels sell order.
     *
     * ### Request URL example ###
    DELETE /api/v1/orders/sell/1
     * ### Success response example ###
     *     {
     *        "id": 0,
     *        "type": "",
     *        "side": "",
     *        "market": "",
     *        "amount": "",
     *        "current_amount": "",
     *        "price": "",
     *        "status": "",
     *        "timestamp": 0,
     *        "fee_taken": "",
     *        "fee_fixed": "",
     *        "fee_percent": "",
     *        "transactions": [
     *          {
     *              "id": 0,
     *              "order_id": 0,
     *              "status": "",
     *              "amount": "",
     *              "price": "",
     *              "fee": "",
     *              "timestamp": 0
     *          }
     *       ]
     *     }
     * ### Error response example ###
     *     {
     *       "status": 400,
     *       "error": "VALIDATION_ERROR"
     *     }
     *     {
     *       "status": 400,
     *       "error": "INCORRECT_DATA"
     *     }
     *
     * @ApiDoc(
     *   resource = true,
     *   description = "Cancels sell order",
     *   statusCodes = {
     *     Response::HTTP_CREATED = "Returned when successful",
     *     Response::HTTP_BAD_REQUEST = "Returned when errors",
     *     Response::HTTP_NOT_FOUND = "Returned when the data are not found",
     *   },
     *   section = "Trade",
     *   authentication = true
     * )
     *
     * @Annotations\Delete("/orders/sell/{id}", requirements = { "id" = "\d+" })
     *
     * @Annotations\RequestParam(name="nonce", requirements="\S+", description="Nonce.")
     *
     * @param int $id
     *
     * @return Response
     *
     * @throws NotFoundException
     */
    public function deleteOrdersSellAction($id)
    {
        if (!$order = $this->get('rest.service.user_order')->getOneBy(['id' => $id, 'side' => Order::SIDE_SELL])) {
            throw new NotFoundException();
        }

        $isError = $this->get('rest.service.deal_submission')->cancelOrder($order);

        if ($isError instanceof ErrorInterface) {
            throw new UnknownErrorException();
        }

        $order = $this->get('rest.repository.order')->find($order->getId());

        $data = new OrderPresenter($order);

        return $this->handleView(
            $this->view($data->presentAsJson(), Response::HTTP_OK)
        );
    }

    /**
     * Cancels order.
     *
     * ### Request URL example ###
     DELETE /api/v1/orders/1
     * ### Success response example ###
     *     {
     *        "id": 0,
     *        "type": "",
     *        "side": "",
     *        "market": "",
     *        "amount": "",
     *        "current_amount": "",
     *        "price": "",
     *        "status": "",
     *        "timestamp": 0,
     *        "fee_taken": "",
     *        "fee_fixed": "",
     *        "fee_percent": "",
     *        "transactions": [
     *          {
     *              "id": 0,
     *              "order_id": 0,
     *              "status": "",
     *              "amount": "",
     *              "price": "",
     *              "fee": "",
     *              "timestamp": 0
     *          }
     *       ]
     *     }
     * ### Error response example ###
     *     {
     *       "status": 400,
     *       "error": "VALIDATION_ERROR"
     *     }
     *     {
     *       "status": 400,
     *       "error": "INCORRECT_DATA"
     *     }
     *
     * @ApiDoc(
     *   resource = true,
     *   description = "Cancels order",
     *   statusCodes = {
     *     Response::HTTP_OK = "Returned when successful",
     *     Response::HTTP_BAD_REQUEST = "Returned when errors",
     *     Response::HTTP_NOT_FOUND = "Returned when the data are not found",
     *   },
     *   section = "Trade",
     *   authentication = true
     * )
     *
     * @Annotations\Delete("/orders/{id}", requirements = { "id" = "\d+" })
     *
     * @Annotations\RequestParam(name="nonce", requirements="\S+", description="Nonce.")
     *
     * @param int $id
     *
     * @return Response
     *
     * @throws NotFoundException
     */
    public function deleteOrderAction($id)
    {
        if (!$order = $this->get('rest.service.user_order')->findNotClosedOrder($id)) {
            throw new NotFoundException();
        }

        $isError = $this->get('rest.service.deal_submission')->cancelOrder($order);

        if ($isError instanceof ErrorInterface) {
            throw new UnknownErrorException();
        }

        $order = $this->get('rest.repository.order')->find($order->getId());

        $data = new OrderPresenter($order);

        return $this->handleView(
            $this->view($data->presentAsJson(), Response::HTTP_OK)
        );
    }

    /**
     * Cancels all orders.
     *
     * ### Request URL example ###
     * DELETE /api/trade/v1/orders
     * ### Success response example ###
     *     {
     *       "buy": [
     *          {
     *              "id": "",
     *              "type": "",
     *              "amount": "",
     *              "current_amount": "",
     *              "price": "",
     *              "timestamp": ""
     *          }
     *      ],
     *      "sell": [
     *          {
     *              "id": "",
     *              "type": "",
     *              "amount": "",
     *              "current_amount": "",
     *              "price": "",
     *              "timestamp": ""
     *          }
     *      ]
     *     }
     * ### Error response example ###
     *     {
     *       "status": 404,
     *       "error": "NOT_FOUND"
     *     },
     *     {
     *       "status": 400,
     *       "error": "INCORRECT_DATA"
     *     }
     *
     * @ApiDoc(
     *   resource = true,
     *   description = "Cancels all orders",
     *   statusCodes = {
     *     Response::HTTP_OK = "Returned when successful",
     *     Response::HTTP_BAD_REQUEST = "Returned when errors",
     *     Response::HTTP_NOT_FOUND = "Returned when the data are not found"
     *   },
     *   section = "Trade",
     *   authentication = true
     * )
     *
     * @Annotations\Delete("/orders")
     *
     * @Annotations\RequestParam(name="market", requirements="\S+", description="Market code name.")
     * @Annotations\RequestParam(name="nonce", requirements="\S+", description="Nonce.")
     *
     * @param ParamFetcherInterface $paramFetcher
     *
     * @return Response
     */
    public function deleteAllOrdersAction(ParamFetcherInterface $paramFetcher)
    {
        if (!$this->getUser()->hasRole(User::ADMIN)) {
            throw new AccessDeniedException();
        }

        $market = $paramFetcher->get('market');

        if (!$market = $this->get('rest.service.market')->getOneBy(['slug' => $market])) {
            throw new NotFoundException();
        }

        $user = $this->getUser();

        $orders = $this->get('rest.service.user_order')
            ->findOpenLimitOrdersWithinMarket($user, $market);

        $dealService = $this->get('rest.service.deal_submission');

        foreach ($orders as $order) {
            $isError = $dealService->cancelOrder($order);

            if ($isError instanceof ErrorInterface) {
                throw new UnknownErrorException();
            }
        }

        $bids = array_values(array_filter($orders, function(Order $o) {
            return $o->getSide() === Order::SIDE_BUY;
        }));

        $asks = array_values(array_filter($orders, function(Order $o) {
            return $o->getSide() === Order::SIDE_SELL;
        }));

        $data = new DealsList($bids, $asks);

        return $this->handleView(
            $this->view($data->presentAsJson(), Response::HTTP_OK)
        );
    }
}
