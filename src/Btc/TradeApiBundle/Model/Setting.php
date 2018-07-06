<?php

namespace Btc\ApiBundle\Model;

class Setting
{
    private $id;

    private $slug;

    private $name;

    private $value;

    private $description;

    private $market;

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

    public function getSlug()
    {
        return $this->slug;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setValue($value)
    {
        $this->value = $value;
        return $this;
    }

    public function getValue()
    {
        return $this->value;
    }

    public function setDescription($description)
    {
        $this->description = $description;
        return $this;
    }

    public function getDescription()
    {
        return $this->description;
    }

    public function setMarket(Market $market = null)
    {
        $this->market = $market;
        return $this;
    }

    public function getMarket()
    {
        return $this->market;
    }
}
