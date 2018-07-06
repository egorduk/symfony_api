<?php

namespace Btc\TradeApiBundle\Model;

use Btc\Component\Market\Model\Order as MarketOrder;

class Order extends MarketOrder
{
    use DealMappingTrait;

    private $status;

    private $transactions = [];

    private $createdAt;

    public static $statusMap = [
        1 => 'open',
        2 => 'completed',
        3 => 'cancelled',
        4 => 'pending_cancel',
        5 => 'closed',
    ];

    public function __construct(array $data = [])
    {
        foreach ($data as $fieldName => $value) {
            $this->{$fieldName} = $value;
        }
    }

    public function getStatus()
    {
        return $this->status;
    }

    public function setTransactions(array $transactions = [])
    {
        $this->transactions = $transactions;
        return $this;
    }

    public function getTransactions()
    {
        return $this->transactions;
    }

    public function getCreatedAt()
    {
        return $this->createdAt;
    }
}
