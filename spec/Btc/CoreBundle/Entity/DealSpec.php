<?php

namespace spec\Btc\CoreBundle\Entity;

use Btc\CoreBundle\Entity\Deal;
use Btc\CoreBundle\Entity\Market;
use Btc\CoreBundle\Entity\Order;
use Btc\CoreBundle\Entity\RestEntityInterface;
use Btc\CoreBundle\Entity\Transaction;
use Doctrine\Common\Collections\ArrayCollection;
use PhpSpec\ObjectBehavior;

class DealSpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->shouldHaveType(Deal::class);
        $this->shouldImplement(RestEntityInterface::class);
    }

    public function it_should_properly_initialize_transactions()
    {
        $transactions = $this->getTransactions()->shouldBeAnInstanceOf(ArrayCollection::class);

        assert($transactions->isEmpty());
    }

    public function it_should_have_correct_count_of_transactions_after_add()
    {
        $this->addTransactions(new Transaction());
        $this->addTransactions(new Transaction());

        $this->getTransactions()->shouldHaveCount(2);
    }

    public function it_should_have_correct_count_of_transactions_after_remove()
    {
        $this->addTransactions(new Transaction());
        $this->addTransactions(new Transaction());
        $this->removeTransactions(1);

        $this->getTransactions()->shouldHaveCount(1);
    }

    public function it_should_return_market()
    {
        $market = new Market();

        $transaction = new Transaction();
        $transaction->setMarket($market);

        $this->addTransactions($transaction);

        $this->getMarket()->shouldReturn($market);
    }

    public function it_should_return_double_total_fees()
    {
        $order = new Order();
        $order->setFeePercent(50);

        $transaction = new Transaction();
        $transaction->setAmount(10);
        $transaction->setPrice(20);
        $transaction->setOrder($order);

        $this->addTransactions($transaction);

        $transaction = new Transaction();
        $transaction->setAmount(12);
        $transaction->setPrice(50);
        $transaction->setOrder($order);

        $this->addTransactions($transaction);

        $this->getFeesCollected()->shouldReturn((float) 400);
    }

    public function it_should_return_int_value()
    {
        $transaction = new Transaction();
        $transaction->setAmount(10);
        $transaction->setPrice(20);

        $this->addTransactions($transaction);

        $transaction = new Transaction();
        $transaction->setAmount(20);
        $transaction->setPrice(35);

        $this->addTransactions($transaction);

        $this->getValue()->shouldReturn(450);
    }

    public function it_should_return_int_total_amount()
    {
        $transaction = new Transaction();
        $transaction->setAmount(10);

        $this->addTransactions($transaction);

        $transaction = new Transaction();
        $transaction->setAmount(20);

        $this->addTransactions($transaction);

        $this->getAmount()->shouldReturn(30);
    }
}
