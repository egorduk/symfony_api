<?php

namespace Btc\ApiBundle\Model;

class Voucher
{
    const STATUS_UNKNOWN = 0;
    const STATUS_OPEN = 1;
    const STATUS_REDEEMED = 2;

    public static $statusMap = [
        1 => 'open',
        2 => 'redeemed',
    ];

    private $id;

    private $amount;

    private $currency;

    private $code;

    private $status;

    private $createdAt;

    private $redeemedAt;

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

    public function setAmount($amount)
    {
        $this->amount = $amount;
        return $this;
    }

    public function getAmount()
    {
        return $this->amount;
    }

    public function setCurrency($currency)
    {
        $this->currency = $currency;
        return $this;
    }

    public function getCurrency()
    {
        return $this->currency;
    }

    public function setCode($code)
    {
        $this->code = $code;
        return $this;
    }

    public function getCode()
    {
        return $this->code;
    }

    public function setStatus($status)
    {
        $this->status = $status;
        return $this;
    }

    public function getStatus()
    {
        return $this->status;
    }

    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    public function setRedeemedAt($redeemedAt)
    {
        $this->redeemedAt = $redeemedAt;
        return $this;
    }

    public function getRedeemedAt()
    {
        return $this->redeemedAt;
    }
}
