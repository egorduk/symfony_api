<?php

namespace Btc\FrontendApiBundle\Controller;

use Btc\CoreBundle\Entity\Order as OrderEntity;
use Btc\FrontendApiBundle\Events\AccountActivityEvents;
use Btc\FrontendApiBundle\Exception\Rest\NoMarketException;
use Btc\FrontendApiBundle\Exception\Rest\NotFoundException;
use Btc\PaginationBundle\Target;
use FOS\RestBundle\Controller\Annotations;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Request\ParamFetcherInterface;
use JMS\Serializer\SerializationContext;
use JMS\Serializer\SerializerBuilder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;

class UserOrderController extends FOSRestController
{
    /**
     * Creates a new order (buy or sell).
     *
     * ### Request URL example ###
     POST /api/v1/orders/open
     body: {"marketId":"1","isBuy":"1","askedUnitPrice":"100","amount":"5","stopPrice":"150"}
     * ### Success response example ###
     *     {
     *       {
     *          "id": 4,
     *          "market_id": 1,
     *          "market_slug": "btc-usd",
     *          "in_wallet_id": 2,
     *          "out_wallet_id": 1,
     *          "fee_reserved": "1.00000000",
     *          "asked_unit_price": "100.00000",
     *          "amount": 5,
     *          "fee_percent": "0.20000000",
     *          "timestamp": 1517484381,
     *          "type": 1,
     *          "reserve_total": "501.00000000",
     *          "current_amount": 0,
     *          "side": "BUY",
     *          "stop_price": "150"
     *      }
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
     *     {
     *       "status": 404,
     *       "error": "NO_MARKET"
     *     }
     *     {
     *       "status": 500,
     *       "error": "UNKNOWN_ERROR"
     *     }
     *
     * @ApiDoc(
     *   resource = true,
     *   description = "Creates a new order (buy or sell)",
     *   output = "Btc\CoreBundle\Entity\Order",
     *   statusCodes = {
     *     Response::HTTP_CREATED = "Returned when successful",
     *     Response::HTTP_BAD_REQUEST = "Returned when errors",
     *     Response::HTTP_NOT_FOUND = "Returned when the data are not found",
     *     Response::HTTP_INTERNAL_SERVER_ERROR = "Returned when errors"
     *   },
     *   section = "Order",
     *   authentication = true
     * )
     *
     * @Annotations\Post("/orders/open")
     *
     * @Annotations\RequestParam(name="marketId", requirements="\d+")
     * @Annotations\RequestParam(name="isBuy", requirements="[01]{1}", default="0", description="0 for sell, 1 for buy")
     * @Annotations\RequestParam(name="askedUnitPrice", requirements="[0-9\.]+", default="0", description="Only for limit order operation")
     * @Annotations\RequestParam(name="stopPrice", requirements="[0-9\.]+", default="0", description="Only for stop-limit order operation")
     * @Annotations\RequestParam(name="amount", requirements="[0-9\.]+")
     *
     * @param Request $request
     *
     * @return Response
     *
     * @throws NoMarketException
     */
    public function postOrderOpenAction(Request $request)
    {
        $marketId = $request->get('marketId', 0);

        if (!$market = $this->get('rest.service.market')->get($marketId)) {
            throw new NoMarketException();
        }

        $orderType = $request->get('askedUnitPrice', 0) > 0 ? OrderEntity::TYPE_LIMIT : OrderEntity::TYPE_MARKET;
        $orderSide = $request->get('isBuy', 0) ? OrderEntity::SIDE_BUY : OrderEntity::SIDE_SELL;

        $stopPrice = $request->get('stopPrice', 0);

        if ($stopPrice > 0 && $orderType === OrderEntity::TYPE_LIMIT) {
            $orderType = OrderEntity::TYPE_STOP_LIMIT;
        }

        $view = $this->get('rest.service.user_order')
            ->getOrderView($request, $market, $this->getUser(), $orderSide, $orderType, $stopPrice, AccountActivityEvents::getOrderEvent($orderSide, $orderType));

        return $this->handleView($view);
    }

    /**
     * Gets user order history.
     *
     * ### Request URL example ###
     * GET /api/v1/orders/history?pageNum=1&limit=10
     * ### Success response example ###
     *     {
     *       {
     *          "orders": [{
     *              "id": 1,
     *              "market": {
     *                  "id": 1,
     *                  "slug": "btc-usd",
     *                  "currency": {
     *                      "id": 2,
     *                      "code": "BTC",
     *                      "sign": "฿",
     *                      "format": 8,
     *                      "crypto": true
     *                  },
     *                  "with_currency": {
     *                      "id": 1,
     *                      "code": "USD",
     *                      "sign": "$",
     *                      "format": 2,
     *                      "crypto": false
     *                  },
     *                  "name": "BTC-USD"
     *              },
     *              "in_wallet": {
     *                  "id": 1,
     *                  "currency": {
     *                      "id": 1,
     *                      "code": "USD",
     *                      "sign": "$",
     *                      "format": 2,
     *                      "crypto": false
     *                  },
     *                  "balance": 10044.24498674,
     *                  "reserved": 3767.39,
     *                  "total": 6273.85498674
     *              },
     *              "out_wallet": {
     *                  "id": 2,
     *                  "currency": {
     *                      "id": 2,
     *                      "code": "BTC",
     *                      "sign": "฿",
     *                      "format": 8,
     *                      "crypto": true
     *                  },
     *                  "balance": 36.5,
     *                  "reserved": 1,
     *                  "total": 35.5
     *              },
     *              "created_at": "2018-01-05T00:00:00+00:00",
     *              "updated_at": "2018-01-20T18:15:59+00:00",
     *              "status": "2",
     *              "current_amount": 1,
     *              "price": 1,
     *              "fee_percent": 1,
     *              "fee_amount_reserved": 1,
     *              "fee_amount_taken": 1,
     *              "type": "1",
     *              "start_quantity": 1,
     *              "transactions": [{
     *                  "id": 1,
     *                  "amount": 1,
     *                  "fee": 1,
     *                  "price": 1,
     *                  "status": 1,
     *                  "type": "maker",
     *                  "platform": "EXM",
     *                  "executed_at": "2017-11-24T04:48:00+00:00"
     *              }],
     *              "side": "SELL",
     *              "reserve_total": 1,
     *              "reserve_spent": 0,
     *              "total_price": 1.01
     *          }],
     *          "total_pages": 1,
     *          "current_page": 1
     *      }
     *     }
     *
     * @ApiDoc(
     *   resource = true,
     *   description = "Gets user order history",
     *   statusCodes = {
     *     Response::HTTP_OK = "Returned when successful"
     *   },
     *   output = "Btc\CoreBundle\Entity\Order",
     *   section = "Order",
     *   authentication = true
     * )
     *
     * @Annotations\QueryParam(name="pageNum", requirements="\d+", default="1", description="The number of page")
     * @Annotations\QueryParam(name="limit", requirements="\d+", default="10", description="The limit of items")
     *
     * @param ParamFetcherInterface $paramFetcher
     *
     * @return Response
     */
    public function getOrdersHistoryAction(ParamFetcherInterface $paramFetcher)
    {
        $pageNum = $paramFetcher->get('pageNum');
        $limit = $paramFetcher->get('limit');

        $user = $this->getUser();

        $orderRepository = $this->get('rest.repository.order');

        $qb = $orderRepository->getUserCompletedTransactionsBaseQueryBuilderWithTxOnly($user);
        $counter = $orderRepository->getUserCompletedTransactionsBaseQueryBuilderWithTxOnly($user);

        $target = new Target($qb);
        $target->setCounterQueryBuilder($counter);

        $request = new Request([
            'page' => $pageNum,
            'limit' => $limit,
        ]);

        $data = $this->get('paginator')->paginate($request, $target, true);

        foreach ($data->getItems() as $order) {
            $transactions = $order->getTransactions();

            if (!empty($transactions)) {
                $price = 0;
                $total = 0;
                $fee = 0;

                foreach ($transactions as $transaction) {
                    $price = bcadd($price, $transaction->getPriceWithFee(), 8);
                    $fee = bcadd($fee, $transaction->getFee(), 8);

                    $transactionTotal = $transaction->getTotal();
                    $transaction->setTotal($transactionTotal);

                    $total = bcadd($total, $transactionTotal, 8);
                }

                $order->setTotalPrice($price);
                $order->setTotal($total);
                $order->setTotalFee($fee);
            }
        }

        $serializer = SerializerBuilder::create()->build();
        $serializedActivities = $serializer->serialize($data->getItems(), 'json', SerializationContext::create()->setGroups(['api']));
        $orders = $serializer->deserialize($serializedActivities, 'array<'.OrderEntity::class.'>', 'json');

        return $this->handleView(
            $this->view([
                'orders' => $orders,
                'total_pages' => $data->getTotalPageCount(),
                'current_page' => $data->getCurrentPageNumber(),
            ], Response::HTTP_OK)
        );
    }

    /**
     * Closes order by order id.
     *
     * ### Request URL example ###
     * GET /api/v1/orders/1/status/close
     * ### Success response example ###
     *     {
     *       {
     *          "id": 2,
     *          "market": {}
     *          "in_wallet": {}
     *          "out_wallet": {}
     *          "created_at": "2018-01-05T00:00:00+00:00",
     *          "updated_at": "2018-02-01T11:47:07+00:00",
     *          "status": "1",
     *          "current_amount": 100,
     *          "price": 10,
     *          "fee_percent": 1,
     *          "fee_amount_reserved": 1,
     *          "fee_amount_taken": 1,
     *          "type": "1",
     *          "start_quantity": 10,
     *          "transactions": [{}]
     *          "side": "SELL",
     *          "reserve_total": 1,
     *          "reserve_spent": 0,
     *          "timestamp": 1517485669
     *       }
     *     }
     * ### Error response example ###
     *     {
     *       "status": 400,
     *       "error": "INCORRECT_DATA"
     *     }
     *     {
     *       "status": 404,
     *       "error": "NOT_FOUND"
     *     }
     *     {
     *       "status": 500,
     *       "error": "UNKNOWN_ERROR"
     *     }
     *
     * @ApiDoc(
     *   resource = true,
     *   description = "Closes order by order id",
     *   statusCodes = {
     *     Response::HTTP_OK = "Returned when successful",
     *     Response::HTTP_BAD_REQUEST = "Returned when errors",
     *     Response::HTTP_NOT_FOUND = "Returned when the data are not found",
     *     Response::HTTP_INTERNAL_SERVER_ERROR = "Returned when errors"
     *   },
     *   section = "Order",
     *   authentication = true
     * )
     *
     * @Annotations\Get("/orders/{orderId}/status/close", requirements = { "orderId" = "\d+" })
     *
     * @param int $orderId
     *
     * @return Response
     *
     * @throws NotFoundException when item do not exist
     */
    public function setOrderStatusCloseAction($orderId)
    {
        if ($order = $this->get('rest.repository.order')->find($orderId)) {
            $this->get('rest.service.deal_submission')->cancelOrder($order);

            return $this->handleView(
                $this->view([
                    $order,
                ], Response::HTTP_OK)
            );
        }

        throw new NotFoundException();
    }

    /**
     * Closes all active markets.
     *
     * ### Request URL example ###
     * PATCH /api/v1/market_orders/active/markets/status/close
     * ### Success response example ###
     *     {
     *        "isSuccess": true
     *     }
     * ### Error response example ###
     *     {
     *       "status": 404,
     *       "error": "NOT_FOUND"
     *     }
     *
     * @ApiDoc(
     *   resource = true,
     *   description = "Closes all active markets",
     *   statusCodes = {
     *     Response::HTTP_OK = "Returned when successful",
     *     Response::HTTP_NOT_FOUND = "Returned when the data are not found"
     *   },
     *   section = "Order",
     *   authentication = true
     * )
     *
     * @Annotations\Patch("/market_orders/active/markets/status/close")
     *
     * @throws NotFoundException when items do not exist
     *
     * @return Response
     */
    public function closeActiveMarketOrdersAction()
    {
        if ($orders = $this->get('rest.repository.order')->findBy(['status' => OrderEntity::STATUS_OPEN])) {
            $serviceUserOrder = $this->get('rest.service.user_order');

            foreach ($orders as $order) {
                $order->setStatus(OrderEntity::STATUS_CLOSED);
                $serviceUserOrder->patch($order, null, false);
            }

            $serviceUserOrder->flushAll();

            return $this->handleView(
                $this->view([
                    'isSuccess' => boolval($orders),
                ], Response::HTTP_OK)
            );
        }

        throw new NotFoundException();
    }
}
