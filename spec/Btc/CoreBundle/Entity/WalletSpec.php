<?php

namespace spec\Btc\CoreBundle\Entity;

use Btc\CoreBundle\Entity\RestEntityInterface;
use Btc\CoreBundle\Entity\Wallet;
use PhpSpec\ObjectBehavior;

class WalletSpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->shouldHaveType(Wallet::class);
        $this->shouldImplement(RestEntityInterface::class);
    }

    public function it_should_have_amounts_defaulted_to_zero()
    {
        $this->getAmountAvailable()->shouldBe(doubleval(0));
        $this->getAmountReserved()->shouldBe(doubleval(0));
        $this->getAmountTotal()->shouldBe(doubleval(0));
    }

    public function it_should_add_increase_total_and_available_amounts_when_crediting()
    {
        $this->getAmountAvailable()->shouldBe(doubleval(0));
        $this->getAmountTotal()->shouldBe(doubleval(0));

        $this->credit(12);
        $this->getAmountAvailable()->shouldBe(doubleval(12));
        $this->getAmountTotal()->shouldBe(doubleval(12));

        $this->credit(8);
        $this->getAmountAvailable()->shouldBe(doubleval(20));
        $this->getAmountTotal()->shouldBe(doubleval(20));
    }

    public function it_should_increase_reserved_and_reduce_available_but_not_total()
    {
        $this->credit(100);
        $this->reserve(10);
        $this->getAmountReserved()->shouldBe(doubleval(10));
        $this->getAmountAvailable()->shouldBe(doubleval(90));
        $this->getAmountTotal()->shouldBe(doubleval(100));
        $this->reserve(40);

        $this->getAmountReserved()->shouldBe(doubleval(50));
        $this->getAmountAvailable()->shouldBe(doubleval(50));
        $this->getAmountTotal()->shouldBe(doubleval(100));
    }

    public function it_should_decrease_total_and_available_amounts_on_debit()
    {
        $this->credit(100);
        $this->debit(10);

        $this->getAmountReserved()->shouldBe(doubleval(0));
        $this->getAmountAvailable()->shouldBe(doubleval(90));
        $this->getAmountTotal()->shouldBe(doubleval(90));
    }

    public function it_should_be_able_to_refund_money_from_reserved()
    {
        $this->credit(100);
        $this->reserve(50);
        $this->refundReserve(50);

        $this->getAmountReserved()->shouldBe(doubleval(0));
        $this->getAmountAvailable()->shouldBe(doubleval(100));
        $this->getAmountTotal()->shouldBe(doubleval(100));
    }

    public function it_should_be_able_to_reduce_reserve()
    {
        $this->setAmountReserved(50);
        $this->setAmountTotal(200);

        $this->reduceReserve(30);

        $this->getAmountReserved()->shouldBe(doubleval(20));
        $this->getAmountTotal()->shouldBe(doubleval(170));
    }

    public function it_should_be_able_to_refund_reserve()
    {
        $this->setAmountReserved(50);
        $this->setAmountAvailable(200);

        $this->refundReserve(10);

        $this->getAmountReserved()->shouldBe(doubleval(40));
        $this->getAmountAvailable()->shouldBe(doubleval(210));
    }
}
