<?php

namespace Btc\FrontendApiBundle\Events;

use Btc\CoreBundle\Entity\Order;
use Btc\CoreBundle\Entity\User;
use Symfony\Component\HttpFoundation\Request;

class UserTradeActivityEvent extends UserActivityEvent
{
    private $order;

    public function __construct(User $user, Request $request, Order $order)
    {
        $this->order = $order;

        parent::__construct($user, $request);
    }

    public function getOrder()
    {
        return $this->order;
    }

    public function getAmountWithCurrency()
    {
        return $this->order->getAmount().' '.$this->order->getAssetCurrencyCode();
    }

    public function getPriceWithCurrency()
    {
        return $this->order->getAskedUnitPrice().' '.$this->order->getFundsCurrencyCode();
    }
}
