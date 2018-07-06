<?php

namespace Btc\TradeApiBundle\Model;

use Btc\ApiBundle\Model\Market;
use Btc\CoreBundle\Entity\Order;

trait DealMappingTrait
{
    public static $statusMap = [
        Order::STATUS_OPEN => 'open',
        Order::STATUS_COMPLETED => 'completed',
        Order::STATUS_CANCELLED => 'cancelled',
        Order::STATUS_PENDING_CANCEL => 'pending_cancel',
    ];

    public static $typeMap = [
        Order::TYPE_LIMIT => 'limit',
        Order::TYPE_MARKET => 'instant',
    ];

    private $market;

    public function setMarket($market)
    {
        $this->market = $market;
        $this->setMarketSlug($market->getSlug());
        $this->setMarketId($market->getId());

        return $this;
    }

    public function getMarket()
    {
        return $this->market;
    }
}
