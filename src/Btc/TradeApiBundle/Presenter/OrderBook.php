<?php

namespace Btc\ApiBundle\Presenter;

use Btc\ApiBundle\Model\Order;

class OrderBook implements PresenterInterface
{
    /**
     * @var array
     */
    private $bids;

    /**
     * @var array
     */
    private $asks;

    /**
     * Initialize open deals presenter with a list of bids and asks
     * to present
     *
     * @param array $bids
     * @param array $asks
     */
    public function __construct(array $bids = [], array $asks = [])
    {
        $this->bids = $bids;
        $this->asks = $asks;
    }

    /**
     * Present open deals
     *
     * @return array
     */
    public function presentAsJson()
    {
        return [
            'bids' => array_map(
                function (Order $deal) {
                    return [
                        "price" => bcadd($deal->getAskedUnitPrice(), 0, 8),
                        "amount" => bcsub($deal->getAmount(), $deal->getCurrentAmount(), 8)
                    ];
                },
                $this->bids
            ),
            'asks' => array_map(
                function (Order $deal) {
                    return [
                        "price" => bcadd($deal->getAskedUnitPrice(), 0, 8),
                        "amount" => bcsub($deal->getAmount(), $deal->getCurrentAmount(), 8)
                    ];
                },
                $this->asks
            )
        ];
    }
}

