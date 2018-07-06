<?php

namespace Btc\ApiBundle\Model;

class Market
{
    private $id;

    private $slug;

    private $name;

    private $currency;

    private $withCurrency;

    private $internal;

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

    public function isInternal()
    {
        return $this->internal;
    }

    public function getSlug()
    {
        return $this->slug;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setCurrency(Currency $currency)
    {
        $this->currency = $currency;
        return $this;
    }

    public function getCurrency()
    {
        return $this->currency;
    }

    public function setWithCurrency(Currency $withCurrency)
    {
        $this->withCurrency = $withCurrency;
        return $this;
    }

    public function getWithCurrency()
    {
        return $this->withCurrency;
    }
}
