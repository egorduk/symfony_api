<?php
namespace Btc\TradeApiBundle\Model;

class Transaction
{
    const STATUS_UNKNOWN = 0;
    const STATUS_EXECUTED = 1;
    const STATUS_COMPLETED = 2;

    const PLATFORM_VIRTEX = "VTX";
    const PLATFORM_BITFINEX = "BFX";

    public static $statusMap = [
        1 => 'executed',
        2 => 'completed',
    ];

    private $id;
    private $order;
    private $orderId;
    private $amount;
    private $price;
    private $fee;
    private $status;
    private $executedAt;
    private $completedAt;

    private $orderSide;

    public function __construct(array $data = [])
    {
        foreach ($data as $fieldName => $value) {
            $this->{$fieldName} = $value;
        }
    }

    public function getId()
    {
        return $this->id;
    }

    public function setOrder(Order $order)
    {
        $this->order = $order;
        return $this;
    }

    public function getOrder()
    {
        return $this->order;
    }

    public function setOrderSide($side)
    {
        $this->orderSide = $side;
        return $this;
    }

    public function getOrderSide()
    {
        return $this->orderSide;
    }


    public function setOrderId($orderId)
    {
        $this->orderId = $orderId;
        return $this;
    }

    public function getOrderId()
    {
        return $this->orderId;
    }


    public function getAmount()
    {
        return $this->amount;
    }

    public function getAbsoluteAmount()
    {
        return abs($this->amount);
    }

    public function getPrice()
    {
        return $this->price;
    }

    public function getStatus()
    {
        return $this->status;
    }

    public function getExecutedAt()
    {
        return $this->executedAt;
    }
    public function getCompletedAt()
    {
        return $this->completedAt;
    }

    public function getFee()
    {
        return $this->fee;
    }

    public function setFee($fee)
    {
        $this->fee = $fee;
        return $this;
    }
}
