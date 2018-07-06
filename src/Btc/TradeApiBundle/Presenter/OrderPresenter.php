<?php namespace Btc\TradeApiBundle\Presenter;

use Btc\CoreBundle\Entity\Order;
use Btc\CoreBundle\Entity\Transaction;
use Btc\TradeApiBundle\Model\Transaction as TransactionModel;

class OrderPresenter implements PresenterInterface
{
    private $order;

    public function __construct(Order $order)
    {
        $this->order = $order;
    }

    /**
     * Present something as json array
     *
     * @return array
     */
    function presentAsJson()
    {
        $order = $this->order;

        $transactions = array_map(function (Transaction $tx) {
                $stampMethod = 'get' . ucfirst($status = TransactionModel::$statusMap[$tx->getStatus()]) . 'At';

                return [
                    'id' => $tx->getId(),
                    'status' => $status,
                    'amount' => bcadd($tx->getAmount(), 0, 8),
                    'price' => bcadd($tx->getPrice(), 0, 8),
                    'fee' => bcadd($tx->getFee(), 0, 8),
                    'timestamp' => $tx->{$stampMethod}()->getTimestamp(),
                ];
            }, $order->getTransactions()->toArray());

        return [
            'id' => $order->getId(),
            'type' => strtolower($order->getType() === 1 ? Order::TYPE_LIMIT_STR : Order::TYPE_MARKET_STR),
            'side' => strtolower($order->getSide()),
            'market' => $order->getMarket()->getSlug(),
            'amount' => bcadd($order->getAmount(), 0, 8),
            'current_amount' => bcadd($order->getCurrentAmount(), 0, 8),
            'price' => bcadd($order->getAskedUnitPrice(), 0, 8),
            'status' => $order->getStatus() === Order::STATUS_OPEN ? 'open' :
                ($order->getStatus() === Order::STATUS_COMPLETED ? 'completed' :
                    ($order->getStatus() === Order::STATUS_CANCELLED ? 'cancelled' : 'closed')),
            'timestamp' => $order->getUpdatedAt()->getTimestamp(),
            'fee_taken' => bcadd($order->getFeeAmountTaken(), 0, 8),
            'fee_fixed' => '0.00000000',
            'fee_percent' => bcadd($order->getFeePercent(), 0, 8),
            'transactions' => $transactions,
        ];
    }
}
