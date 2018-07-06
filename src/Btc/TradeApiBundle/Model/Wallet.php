<?php

namespace Btc\ApiBundle\Model;

class Wallet
{
    private $id;

    private $currency;

    private $user;

    private $total;

    private $reserved;

    private $available;

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

    public function setId($id)
    {
        $this->id = $id;
        return $this;
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

    public function getTotal()
    {
        return $this->total;
    }

    public function getReserved()
    {
        return $this->reserved;
    }

    public function getAvailable()
    {
        return $this->available;
    }

    public function setUser(User $user)
    {
        $this->user = $user;
        return $this;
    }

    public function getUser()
    {
        return $this->user;
    }
}
