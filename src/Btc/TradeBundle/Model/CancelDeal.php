<?php

namespace Btc\TradeBundle\Model;

class CancelDeal
{
    private $id;

    public function __construct($id)
    {
        $this->setId($id);
    }

    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    public function getId()
    {
        return intval($this->id);
    }
}
