<?php

namespace Btc\TradeApiBundle\Presenter;

class MergedOrderBook implements PresenterInterface
{
    private $bids;
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
                function ($deal) {
                    return [
                        'price' => bcadd($deal['price'], 0, 8),
                        'amount' => bcadd($deal['amount'], 0, 8)
                    ];
                },
                $this->bids
            ),
            'asks' => array_map(
                function ($deal) {
                    return [
                        'price' => bcadd($deal['price'], 0, 8),
                        'amount' => bcadd($deal['amount'], 0, 8)
                    ];
                },
                $this->asks
            )
        ];
    }
}

