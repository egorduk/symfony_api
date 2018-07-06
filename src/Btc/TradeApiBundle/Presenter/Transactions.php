<?php

namespace Btc\TradeApiBundle\Presenter;

class Transactions implements PresenterInterface
{
    private $transactions;

    /**
     * Initialize transactions presenter with a list of transactions to present
     *
     * @param array $transactions
     */
    public function __construct(array $transactions)
    {
        $this->transactions = $transactions;
    }

    /**
     * Present transactions
     *
     * @return array
     */
    public function presentAsJson()
    {
        return ['transactions' => array_map(function($transaction) {
            return [
                'id' => $transaction['id'],
                'amount' => bcadd($transaction['amount'], 0, 8),
                'price' => bcadd($transaction['price'], 0, 8),
                'side' => $transaction['side'],
                'timestamp' => $transaction['completedAt']->getTimestamp(),
            ];
        }, $this->transactions)];
    }
}
