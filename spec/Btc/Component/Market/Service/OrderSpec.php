<?php

namespace spec\Btc\Component\Market\Service;

use Btc\CoreBundle\Entity\Order as OrderEntity;
use Btc\Component\Market\Model\Order as OrderModel;
use Btc\Component\Market\Service\Order;
use Doctrine\DBAL\Connection;
use PhpSpec\ObjectBehavior;

class OrderSpec extends ObjectBehavior
{
    function let(Connection $db, OrderModel $order)
    {
        //
    }

    function it_is_initializable($db, $order)
    {
        $this->beConstructedWith($db, $order);
        $this->shouldHaveType(Order::class);
    }

    function it_should_lock_the_order($db, $order)
    {
        $order->getId()->shouldBeCalled()->willReturn(1);
        $this->beConstructedWith($db, $order);
        $db->fetchColumn(Order::SQL_LOCK, [1])->shouldBeCalled();

        $this->lock();
    }

    function it_should_treat_pending_cancel_status_as_already_cancelled($db, $order)
    {
        $this->expectLock($db, $order, OrderEntity::STATUS_PENDING_CANCEL);

        $this->isCancelled()->shouldBe(true);
    }

    function it_should_treat_cancelled_status_as_already_cancelled($db, $order)
    {
        $this->expectLock($db, $order, OrderEntity::STATUS_CANCELLED);

        $this->isCancelled()->shouldBe(true);
    }

    function it_should_treat_open_status_as_not_cancelled($db, $order)
    {
        $this->expectLock($db, $order, OrderEntity::STATUS_OPEN);

        $this->isCancelled()->shouldBe(false);
    }

    function it_should_treat_open_status_as_not_completed($db, $order)
    {
        $this->expectLock($db, $order, OrderEntity::STATUS_OPEN);

        $this->isCompleted()->shouldBe(false);
    }

    function it_should_cancel_the_order($db, $order)
    {
        $order->getId()->shouldBeCalled()->willReturn(1);
        $this->beConstructedWith($db, $order);
        $db->executeUpdate(
            Order::SQL_CANCEL,
            [OrderEntity::STATUS_PENDING_CANCEL, 1]
        )->shouldBeCalled();

        $this->cancel();
    }

    private function expectLock($db, $buy, $statusToReturn)
    {
        $buy->getId()->shouldBeCalled()->willReturn(1);
        $this->beConstructedWith($db, $buy);
        $db->fetchColumn(Order::SQL_LOCK, [1])
            ->shouldBeCalled()
            ->willReturn($statusToReturn);

        $this->lock();
    }
}
