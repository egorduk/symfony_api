<?php

namespace Btc\TradeApiBundle\Presenter;

class Ticker implements PresenterInterface
{
    const LOWEST_ASK = 'lowest.ask';
    const HIGHEST_BID = 'highest.bid';
    const TX_LAST_PRICE = 'last.price';
    const TX_VOLUME_24 = 'volume24';
    const TX_VOLUME_CRYPTO24 = 'volume24.crypto';
    const TX_HIGH_24 = 'high24';
    const TX_LOW_24 = 'low24';

    private $tickerData;


    public function __construct(array $tickerData)
    {
        $this->tickerData = $tickerData;
    }

    public function presentAsJson()
    {
        return [
            'last' => bcadd($this->tickerData[self::TX_LAST_PRICE], 0, 8),
            'high' => bcadd($this->tickerData[self::TX_HIGH_24], 0, 8),
            'low' => bcadd($this->tickerData[self::TX_LOW_24], 0, 8),
            'volume' => bcadd($this->tickerData[self::TX_VOLUME_CRYPTO24], 0, 8),
            'bid' => bcadd($this->tickerData[self::HIGHEST_BID], 0, 8),
            'ask' => bcadd($this->tickerData[self::LOWEST_ASK], 0, 8),
        ];
    }
}
