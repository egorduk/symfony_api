<?php

namespace Btc\TradeApiBundle\Presenter;

use Btc\CoreBundle\Entity\Transaction;

class AccountTransactions implements PresenterInterface
{
    private $transactions;


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
        return ['transactions' => array_map(function (Transaction $tx) {
            return [
                'id' => $tx->getId(),
                'order_id' => $tx->getOrder()->getId(),
                'status' => $tx->getStatus(),
                'amount' => bcadd($tx->getAmount(), 0, 8),
                'price' => bcadd($tx->getPrice(), 0, 8),
                'fee' => bcadd($tx->getFee(), 0, 8),
                'timestamp' => $tx->getExecutedAt()->getTimestamp(),
            ];
        }, $this->transactions)];
    }
}
