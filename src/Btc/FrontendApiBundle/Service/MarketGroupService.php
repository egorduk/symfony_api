<?php

namespace Btc\FrontendApiBundle\Service;

use Btc\CoreBundle\Model\MarketInfo;
use Btc\CoreBundle\Entity\Market;
use Doctrine\Common\Persistence\ObjectRepository;

class MarketGroupService
{
    private $redis;
    private $markets;

    public function __construct(RestRedis $redis, ObjectRepository $markets)
    {
        $this->redis = $redis;
        $this->markets = $markets;
    }

    public function getTradingMarkets()
    {
        return $this->markets->findAllForTrading();
    }

    public function getMarketList()
    {
        $markets = array_map(function (Market $market) {
            return new MarketInfo($market);
        }, (array) $this->getTradingMarkets());

        $result = [];

        foreach ($markets as $market) {
            $result[] = [
                'info' => $market,
                'price' => 0,
            ];
        }

        return $result;
    }

    public function getMarketListWithLastPrices()
    {
        $markets = $this->getMarketList();

        // get the last price for each market
        $prices = $this->redis->mGet(array_map(function (array $m) {
            return 'last.price.'.$m['info']->slug();
        }, $markets));

        if (!$prices) {
            return $markets;
        }
        
        for ($i = 0; $i < count($prices); ++$i) {
            $markets[$i]['price'] = floatval($prices[$i]);
        }

        return $markets;
    }

    public function getMarketListWithLastPricesGroupedByCrypto()
    {
        $markets = [];

        foreach ($this->getMarketListWithLastPrices() as $m) {
            $markets[$m['info']->currencyBase()->getCode()][] = $m;
        }

        return $markets;
    }
}
