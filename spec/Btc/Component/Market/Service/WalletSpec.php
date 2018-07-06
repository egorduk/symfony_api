<?php

namespace spec\Btc\Component\Market\Service;

use Btc\Component\Market\Model\Order;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Doctrine\DBAL\Connection;
use Btc\Component\Market\Service\Wallet;

class WalletSpec extends ObjectBehavior
{
    const ID = 1;

    function let(Connection $db)
    {
        $this->beConstructedWith($db);
    }

    function it_is_initializable()
    {
        $this->shouldHaveType('Btc\Component\Market\Service\Wallet');
    }

    function it_should_reserve_amount_in_wallet($db)
    {
        $this->expectLockCalled(
            $db,
            ['amount_available' => 120, 'amount_reserved' => 0]
        );

        $db->executeUpdate(Wallet::SQL_RESERVE, [
            'amount' => 120,
            'wallet' => self::ID
        ])->shouldBeCalled();

        $this->reserve(self::ID, 120);
    }

    function it_should_throw_exception_if_insufficient_funds_for_reserve($db) {
        $this->expectLockCalled(
            $db,
            ['amount_available' => 90, 'amount_reserved' => 0]
        );

        $db->executeUpdate(Wallet::SQL_RESERVE, [
                'amount' => 120,
                'wallet' => self::ID
            ])->shouldNotBeCalled();

        $this->shouldThrow('Btc\Component\Market\Exception\InsufficientBalanceException')->duringReserve(self::ID, 120);
    }

    function it_should_set_fee_details_for_market_buy_order(Order $order, $db)
    {
        $db->fetchAssoc(Wallet::SQL_LOCK, [self::ID])
            ->shouldBeCalled()
            ->willReturn(['amount_available' => 200, 'amount_reserved' => 0]);
        $this->lock(self::ID);
    }

    function it_should_credit_amount_in_wallet($db)
    {
        $db->executeUpdate(Wallet::SQL_CREDIT, [
            'amount' => 120,
            'wallet' => self::ID
        ])->shouldBeCalled();

        $this->credit(self::ID, 120);
    }

    function it_should_debit_amount_in_wallet($db)
    {
        $this->expectLockCalled(
            $db,
            ['amount_available' => 200, 'amount_reserved' => 0]
        );

        $db->executeUpdate(Wallet::SQL_DEBIT, [
            'amount' => 120,
            'wallet' => self::ID
        ])->shouldBeCalled();

        $this->debit(self::ID, 120);
    }

    function it_should_lock_only_once($db)
    {
        $this->expectLockCalled(
            $db,
            ['amount_available' => 200, 'amount_reserved' => 0]
        );

        $this->lock(self::ID);
        $this->lock(self::ID);
    }

    function it_should_be_able_to_reduce_from_reserve($db)
    {
        $this->expectLockCalled(
            $db,
            ['amount_available' => 130, 'amount_reserved' => 120]
        );

        $db->executeUpdate(
            Wallet::SQL_REDUCE_RESERVE,
            ['amount' => 120, 'wallet' => self::ID]
        )->shouldBeCalled();

        $this->reduceFromReserve(self::ID, 120);
    }

    function it_throws_exception_when_not_enough_reserve_balance($db)
    {
        $this->expectLockCalled(
            $db,
            ['amount_available' => 130, 'amount_reserved' => 120]
        );

        $this->shouldThrow('Btc\Component\Market\Exception\InsufficientBalanceException')
            ->duringReduceFromReserve(self::ID, 140);
    }

    function it_should_be_able_to_refund_from_reserve_to_available_balance($db)
    {
        $this->expectLockCalled(
            $db,
            ['amount_available' => 120, 'amount_reserved' => 120]
        );

        $db->executeUpdate(
            Wallet::SQL_REFUND_FROM_RESERVE,
            ['amount' => 60, 'wallet' => self::ID]
        )->shouldBeCalled();

        $this->refundFromReserve(self::ID, 60);
    }

    function it_throws_an_exception_when_refunding_amount_is_larger_than_reserved_balance($db)
    {
        $this->expectLockCalled(
            $db,
            ['amount_available' => 120, 'amount_reserved' => 10]
        );

        $this->shouldThrow('Btc\Component\Market\Exception\InsufficientBalanceException')
            ->duringRefundFromReserve(self::ID, 10.2);
    }

    public function it_should_reduce_from_total_with_adjusting_for_error()
    {
        $this->validateBalanceWithPrecision("4.01", "4.00999999", 4)->shouldReturn(true);
        $this->validateBalanceWithPrecision("4.01", "4.01999999", 4)->shouldReturn(true);
        $this->validateBalanceWithPrecision("4.021", "4.01999999", 4)->shouldReturn(false);
        $this->validateBalanceWithPrecision("4.0201", "4.01999999", 4)->shouldReturn(false);
        $this->validateBalanceWithPrecision("4.02001", "4.01999999", 4)->shouldReturn(true);
        $this->validateBalanceWithPrecision("4.03", "4.01999999", 4)->shouldReturn(false);
        $this->validateBalanceWithPrecision("4.03", "0.01999999", 4)->shouldReturn(false);
        $this->validateBalanceWithPrecision("4.03", "-100000.01999999", 4)->shouldReturn(false);
        $this->validateBalanceWithPrecision("4.01999999", "4.01999999", 4)->shouldReturn(true);
        $this->validateBalanceWithPrecision("4.01999999", "4.0199", 4)->shouldReturn(true);
        $this->validateBalanceWithPrecision("4.01999999", "4.019", 4)->shouldReturn(false);
        $this->validateBalanceWithPrecision("4.01999999", "123456789456156.01999999", 4)->shouldReturn(true);
        $this->validateBalanceWithPrecision("40000000.01999999", "123456789456156.01999999", 4)->shouldReturn(true);
        $this->validateBalanceWithPrecision("4.00", "4.00999999", 4)->shouldReturn(true);
        $this->validateBalanceWithPrecision("4.00", "4.00000000", 4)->shouldReturn(true);
        $this->validateBalanceWithPrecision("4.00", "3.99999999", 4)->shouldReturn(true);
        $this->validateBalanceWithPrecision("4.00", "3.99991111", 4)->shouldReturn(true);
        $this->validateBalanceWithPrecision("100", "99.99999870", 4)->shouldReturn(true);
    }

    /**
     * @param $db
     * @param array $balance keys: amount_availabe, amount_reserved
     */
    private function expectLockCalled($db, $balances)
    {
        $db->fetchAssoc(Wallet::SQL_LOCK, [self::ID])
            ->shouldBeCalledTimes(1)
            ->willReturn($balances);
    }
}
