<?php

namespace Btc\TradeApiBundle\Presenter;

use Btc\CoreBundle\Entity\Order;

class DealsList implements PresenterInterface
{
    private $buyOrders;
    private $sellOrders;

    /**
     * Initialize open orders presenter with a list of buy and sell orders
     * to present
     *
     * @param array $buyOrders
     * @param array $sellOrders
     */
    public function __construct(array $buyOrders = [], array $sellOrders = [])
    {
        $this->buyOrders = $buyOrders;
        $this->sellOrders = $sellOrders;
    }

    /**
     * Present open orders
     *
     * @return array
     */
    public function presentAsJson()
    {
        return [
            'buy' => array_map(
                function (Order $order) {
                    return [
                        'id' => $order->getId(),
                        'type' => $order->getType() === 1 ? strtolower(Order::TYPE_LIMIT_STR) : strtolower(Order::TYPE_MARKET_STR),
                        'amount' => bcadd($order->getAmount(), 0, 8),
                        'current_amount' => bcadd($order->getCurrentAmount(), 0, 8),
                        'price' => bcadd($order->getAskedUnitPrice(), 0, 8),
                        'timestamp' => $order->getCreatedAt()->getTimestamp(),
                    ];
                },
                $this->buyOrders
            ),
            'sell' => array_map(
                function (Order $order) {
                    return [
                        'id' => $order->getId(),
                        'type' => $order->getType() === 1 ? strtolower(Order::TYPE_LIMIT_STR) : strtolower(Order::TYPE_MARKET_STR),
                        'amount' => bcadd($order->getAmount(), 0, 8),
                        'current_amount' => bcadd($order->getCurrentAmount(), 0, 8),
                        'price' => bcadd($order->getAskedUnitPrice(), 0, 8),
                        'timestamp' => $order->getCreatedAt()->getTimestamp(),
                    ];
                },
                $this->sellOrders
            )
        ];
    }
}
