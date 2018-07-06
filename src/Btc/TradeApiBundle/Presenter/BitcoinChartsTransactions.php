<?php

namespace Btc\ApiBundle\Presenter;

use Btc\ApiBundle\Model\Transaction;

class BitcoinChartsTransactions implements PresenterInterface
{
    /**
     * @var array
     */
    private $transactions;

    /**
     * Initialize transactions presenter with a list of transactions
     * to present
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
        $transactions = array_map(function (Transaction $tx) {
            $stampMethod = 'get' . ucfirst($status = Transaction::$statusMap[$tx->getStatus()]) . 'At';

            return [
                'tid' => $tx->getId(),
                'amount' => bcadd($tx->getAmount(), 0, 8),
                'price' => bcadd($tx->getPrice(), 0, 8),
                'date' => $tx->{$stampMethod}()->getTimestamp(),
            ];
        }, $this->transactions);
        $ids = [];
        foreach ($transactions as $key => $row) {
            $ids[$key] = $row['tid'];
        }
        array_multisort($ids, SORT_ASC, $transactions);
        return $transactions;
    }
}
