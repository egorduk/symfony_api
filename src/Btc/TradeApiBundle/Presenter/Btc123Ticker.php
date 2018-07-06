<?php

namespace Btc\ApiBundle\Presenter;

class Btc123Ticker implements PresenterInterface
{
    const LOWEST_ASK = "lowest.ask";
    const HIGHEST_BID = "highest.bid";
    const TX_LAST_PRICE = "last.price";
    const TX_VOLUME_24 = "volume24";
    const TX_VOLUME_CRYPTO24 = "volume24.crypto";
    const TX_HIGH_24 = "high24";
    const TX_LOW_24 = "low24";

    /**
     * @var array
     */
    private $tickerData;

    /**
     * Initialize presenter with a list of wallets
     * to present
     *
     * @param array $wallets
     */
    public function __construct(array $tickerData)
    {
        $this->tickerData = $tickerData;
    }

    /**
     * Present transactions
     *
     * @return array
     */
    public function presentAsJson()
    {
        return [
            'ticker' => [
                'high' => bcadd($this->tickerData[self::TX_HIGH_24], 0, 8),
                'low' => bcadd($this->tickerData[self::TX_LOW_24], 0, 8),
                'buy' => bcadd($this->tickerData[self::HIGHEST_BID], 0, 8),
                'sell' => bcadd($this->tickerData[self::LOWEST_ASK], 0, 8),
                'last' => bcadd($this->tickerData[self::TX_LAST_PRICE], 0, 8),
                'vol' => bcadd($this->tickerData[self::TX_VOLUME_CRYPTO24], 0, 8),
            ]
        ];
    }
}

