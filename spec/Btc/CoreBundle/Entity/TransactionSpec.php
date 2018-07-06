<?php

namespace spec\Btc\CoreBundle\Entity;

use Btc\CoreBundle\Entity\Order;
use Btc\CoreBundle\Entity\Transaction;
use PhpSpec\ObjectBehavior;

class TransactionSpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->shouldHaveType(Transaction::class);
    }

    public function it_should_set_status_and_type_by_default()
    {
        $this->getType()->shouldBe('');
        $this->getStatus()->shouldBe(Transaction::STATUS_UNKNOWN);
    }

    public function it_should_get_order_value()
    {
        $this->setAmount(10);
        $this->setPrice(20);

        $this->getValue()->shouldBe(200);
    }

    public function it_should_get_fee()
    {
        $order = new Order();
        $order->setFeePercent(10);

        $this->setAmount(10);
        $this->setPrice(20);
        $this->setOrder($order);

        $this->getFee()->shouldBe('20.0');
    }
}
