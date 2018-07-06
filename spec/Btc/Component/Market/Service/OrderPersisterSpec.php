<?php

namespace spec\Btc\Component\Market\Service;

use Btc\Component\Market\Model\Order as OrderModel;
use Btc\Component\Market\Service\OrderPersister;
use Btc\CoreBundle\Entity\Order;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\AbstractPlatform as Platform;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class OrderPersisterSpec extends ObjectBehavior
{
    function let(Connection $db, Platform $platform)
    {
        $db->getDatabasePlatform()->willReturn($platform);
        $platform->getDateTimeFormatString()->willReturn('Y-m-d H:i');

        $this->beConstructedWith($db);
    }

    function it_is_initializable()
    {
        $this->shouldHaveType(OrderPersister::class);
    }

    function it_should_be_able_to_persist_buy_order(OrderModel $order, Connection $db)
    {
        $ts = $this->now();
        $data = [
            'market_id' => 1,
            'status' => Order::STATUS_OPEN,
            'type' => Order::TYPE_LIMIT,
            'fee_percent' => 5,
            'fee_amount_reserved' => 20,
            'fee_amount_taken' => 0,
            'asked_unit_price' => 200,
            'amount' => 1.5,
            'current_amount' => 0,
            'out_wallet_id' => 1,
            'in_wallet_id' => 2,
            'side' => Order::SIDE_BUY,
            'reserve_total' => 315,
            'reserve_spent' => 0,
            'updated_at' => $ts, // may be sometimes not the same
            'created_at' => $ts,
            'stop_price' => 0,
        ];

        $order->getSide()->shouldBeCalled()->willReturn(Order::SIDE_BUY);
        $order->getMarketId()->shouldBeCalled()->willReturn(1);
        $order->getType()->shouldBeCalled()->willReturn(Order::TYPE_LIMIT);
        $order->getFeePercent()->shouldBeCalled()->willReturn(5);
        $order->getFeeReserved()->shouldBeCalled()->willReturn(20);
        $order->getAskedUnitPrice()->shouldBeCalled()->willReturn(200);
        $order->getAmount()->shouldBeCalled()->willReturn(1.5);
        $order->getOutWalletId()->shouldBeCalled()->willReturn(1);
        $order->getInWalletId()->shouldBeCalled()->willReturn(2);
        $order->getReserveTotal()->shouldBeCalled()->willReturn(315);
        $order->setId(5)->shouldBeCalled();
        $order->setTimestamp(Argument::any())->shouldBeCalled();
        $order->getStopPrice()->shouldBeCalled()->willReturn(0);

        $db->lastInsertId()->shouldBeCalled()->willReturn(5);
        $db->insert('orders', $data)->shouldBeCalled();

        $this->persistOrder($order);
    }

    function it_should_be_able_to_persist_sell_order(OrderModel $order, Connection $db)
    {
        $ts = $this->now();
        $data = [
            'market_id' => 1,
            'status' => Order::STATUS_OPEN,
            'type' => Order::TYPE_LIMIT,
            'fee_percent' => 5,
            'fee_amount_reserved' => 20,
            'fee_amount_taken' => 0,
            'asked_unit_price' => 200,
            'amount' => 1.5,
            'current_amount' => 0,
            'out_wallet_id' => 1,
            'in_wallet_id' => 2,
            'side' => Order::SIDE_SELL,
            'reserve_total' => 1.5,
            'reserve_spent' => 0,
            'updated_at' => $ts, // may be sometimes not the same
            'created_at' => $ts,
            'stop_price' => 0,
        ];

        $order->getSide()->shouldBeCalled()->willReturn(Order::SIDE_SELL);
        $order->getMarketId()->shouldBeCalled()->willReturn(1);
        $order->getType()->shouldBeCalled()->willReturn(Order::TYPE_LIMIT);
        $order->getFeePercent()->shouldBeCalled()->willReturn(5);
        $order->getFeeReserved()->shouldBeCalled()->willReturn(20);
        $order->getAskedUnitPrice()->shouldBeCalled()->willReturn(200);
        $order->getAmount()->shouldBeCalled()->willReturn(1.5);
        $order->getOutWalletId()->shouldBeCalled()->willReturn(1);
        $order->getInWalletId()->shouldBeCalled()->willReturn(2);
        $order->getReserveTotal()->shouldBeCalled()->willReturn(1.5);
        $order->setId(5)->shouldBeCalled();
        $order->setTimestamp(Argument::any())->shouldBeCalled();
        $order->getStopPrice()->shouldBeCalled()->willReturn(0);

        $db->lastInsertId()->shouldBeCalled()->willReturn(5);
        $db->insert('orders', $data)->shouldBeCalled();

        $this->persistOrder($order);
    }

    function it_should_be_able_to_persist_stop_limit_order(OrderModel $order, Connection $db)
    {
        $ts = $this->now();
        $data = [
            'market_id' => 1,
            'status' => Order::STATUS_OPEN,
            'type' => Order::TYPE_STOP_LIMIT,
            'fee_percent' => 5,
            'fee_amount_reserved' => 20,
            'fee_amount_taken' => 0,
            'asked_unit_price' => 200,
            'amount' => 1.5,
            'current_amount' => 0,
            'out_wallet_id' => 1,
            'in_wallet_id' => 2,
            'side' => Order::SIDE_SELL,
            'reserve_total' => 1.5,
            'reserve_spent' => 0,
            'updated_at' => $ts, // may be sometimes not the same
            'created_at' => $ts,
            'stop_price' => 100,
        ];

        $order->getSide()->shouldBeCalled()->willReturn(Order::SIDE_SELL);
        $order->getMarketId()->shouldBeCalled()->willReturn(1);
        $order->getType()->shouldBeCalled()->willReturn(Order::TYPE_STOP_LIMIT);
        $order->getFeePercent()->shouldBeCalled()->willReturn(5);
        $order->getFeeReserved()->shouldBeCalled()->willReturn(20);
        $order->getAskedUnitPrice()->shouldBeCalled()->willReturn(200);
        $order->getAmount()->shouldBeCalled()->willReturn(1.5);
        $order->getOutWalletId()->shouldBeCalled()->willReturn(1);
        $order->getInWalletId()->shouldBeCalled()->willReturn(2);
        $order->getReserveTotal()->shouldBeCalled()->willReturn(1.5);
        $order->setId(5)->shouldBeCalled();
        $order->setTimestamp(Argument::any())->shouldBeCalled();
        $order->getStopPrice()->shouldBeCalled()->willReturn(100);

        $db->lastInsertId()->shouldBeCalled()->willReturn(5);
        $db->insert('orders', $data)->shouldBeCalled();

        $this->persistOrder($order);
    }
}

