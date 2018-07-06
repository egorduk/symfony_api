<?php

namespace spec\Btc\CoreBundle\Entity;

use Btc\CoreBundle\Entity\Order;
use Btc\CoreBundle\Entity\RestEntityInterface;
use Btc\CoreBundle\Entity\Transaction;
use Btc\CoreBundle\Entity\Wallet;
use Doctrine\Common\Collections\ArrayCollection;
use PhpSpec\ObjectBehavior;

class OrderSpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->shouldHaveType(Order::class);
        $this->shouldImplement(RestEntityInterface::class);
    }

    public function it_should_properly_initialize_transactions()
    {
        $transactions = $this->getTransactions()->shouldBeAnInstanceOf(ArrayCollection::class);

        assert($transactions->isEmpty());
    }

    public function it_should_properly_initialize_type_and_status()
    {
        $this->getStatus()->shouldBe(Order::STATUS_OPEN);
        $this->getType()->shouldBe(Order::TYPE_LIMIT);
    }

    public function it_is_fulfilled()
    {
        $this->setAmount(1);
        $this->setCurrentAmount(1);

        $this->isFulfilled()->shouldBe(true);
    }

    public function it_is_not_fulfilled()
    {
        $this->setAmount(10);
        $this->setCurrentAmount(1);

        $this->isFulfilled()->shouldBe(false);
    }

    public function it_should_get_unfulfilled_order_value_with_fee()
    {
        $this->setType(Order::TYPE_MARKET);
        $this->setCurrentAmount(20);
        $this->setAskedUnitPrice(10);
        $this->setFeeAmountTaken(10);

        $this->getUnfulfilledOrderValueWithFee()->shouldBe((float) -210);
    }

    public function it_should_get_unfulfilled_order_value()
    {
        $this->setCurrentAmount(20);
        $this->setAskedUnitPrice(10);
        $this->setAmount(50);
        $this->setAskedUnitPrice(10);

        $this->getUnfulfilledOrderValue()->shouldBe((float) 300);
    }

    public function it_should_get_order_value_for_limit_type()
    {
        $this->setType(Order::TYPE_LIMIT);
        $this->setAmount(10);
        $this->setAskedUnitPrice(10);

        $this->getOrderValue()->shouldBe((float) 100);
    }

    public function it_should_get_order_value_for_market_type()
    {
        $this->setType(Order::TYPE_MARKET);

        $transaction = new Transaction();
        $transaction->setAmount(10);
        $transaction->setPrice(20);

        $this->addTransactions($transaction);

        $transaction = new Transaction();
        $transaction->setAmount(12);
        $transaction->setPrice(50);

        $this->getOrderValue()->shouldBe(200);
    }

    public function it_should_refund_reserve_out_wallet()
    {
        $wallet = new Wallet();
        $wallet->setAmountReserved(10);
        $wallet->setAmountAvailable(20);

        $this->setOutWallet($wallet);
        $this->refundReserveOutWallet(3);

        $this->getOutWallet()->getAmountReserved()->shouldBe((float) 7);
        $this->getOutWallet()->getAmountAvailable()->shouldBe((float) 23);
    }

    public function it_should_reduce_reserve_out_wallet()
    {
        $wallet = new Wallet();
        $wallet->setAmountReserved(10);
        $wallet->setAmountTotal(200);

        $this->setOutWallet($wallet);
        $this->reduceReserveOutWallet(3);

        $this->getOutWallet()->getAmountReserved()->shouldBe((float) 7);
        $this->getOutWallet()->getAmountTotal()->shouldBe((float) 197);
    }

    public function it_should_get_amount_left()
    {
        $this->setAmount(150);
        $this->setCurrentAmount(100);

        $this->getAmountLeft()->shouldBe((float) 50);
    }

    public function it_should_credit_in_wallet()
    {
        $wallet = new Wallet();
        $wallet->setAmountTotal(100);
        $wallet->setAmountAvailable(100);

        $this->setInWallet($wallet);
        $this->creditInWallet(10);

        $this->getInWallet()->getAmountAvailable()->shouldBe((float) 110);
        $this->getInWallet()->getAmountTotal()->shouldBe((float) 110);
    }

    public function it_is_completed()
    {
        $this->setStatus(Order::STATUS_COMPLETED);

        $this->isCompleted()->shouldBe(true);
    }

    public function it_is_market_type()
    {
        $this->setType(Order::TYPE_MARKET);

        $this->isMarket()->shouldBe(true);
    }

    public function it_is_not_completed()
    {
        $this->setStatus(Order::STATUS_OPEN);

        $this->isCompleted()->shouldBe(false);
    }

    public function it_is_not_market_type()
    {
        $this->setType(Order::TYPE_LIMIT);

        $this->isMarket()->shouldBe(false);
    }

    public function it_should_get_amount()
    {
        $this->setAmount(20);

        $this->getAmount()->shouldBe((float) 20);
    }

    public function it_should_get_amount_fee_left()
    {
        $this->setFeeAmountReserved(100);
        $this->setFeeAmountTaken(50);

        $this->getFeeAmountLeft()->shouldBe((float) 50);
    }
}
