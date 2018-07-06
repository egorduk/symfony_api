<?php

namespace spec\Btc\Component\Market\Service;

use Btc\Component\Market\Model\Voucher;
use Btc\Component\Market\Service\VoucherPersister;
use Btc\CoreBundle\Entity\Currency;
use Btc\CoreBundle\Entity\User;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\AbstractPlatform as Platform;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class VoucherPersisterSpec extends ObjectBehavior
{
    function let(Connection $db, Platform $platform)
    {
        $db->getDatabasePlatform()->willReturn($platform);
        $platform->getDateTimeFormatString()->willReturn('Y-m-d H:i');

        $this->beConstructedWith($db);
    }

    function it_is_initializable()
    {
        $this->shouldHaveType(VoucherPersister::class);
    }

    function it_should_be_able_to_persist_voucher(Voucher $voucher, Connection $db, Currency $currency, User $user)
    {
        $ts = $this->now();
        $data = [
            'currency_id' => 1,
            'status' => Voucher::STATUS_OPEN,
            'amount' => 50,
            'created_at' => $ts,
            'created_by_user_id' => 1,
            'code' => 1234567890123456,
        ];

        $voucher->getCurrency()->willReturn($currency);
        $currency->getId()->shouldBeCalled()->willReturn(1);
        $voucher->getStatus()->shouldBeCalled()->willReturn(Voucher::STATUS_OPEN);
        $voucher->getAmount()->shouldBeCalled()->willReturn(50);
        $voucher->getIssuer()->willReturn($user);
        $user->getId()->shouldBeCalled()->willReturn(1);
        $voucher->getCode()->shouldBeCalled()->willReturn(1234567890123456);

        $voucher->setId(5)->shouldBeCalled();
        $voucher->setTimestamp(Argument::any())->shouldBeCalled();

        $db->lastInsertId()->shouldBeCalled()->willReturn(5);
        $db->insert('vouchers', $data)->shouldBeCalled();

        $this->persist($voucher);
    }

    function it_should_be_able_to_update_voucher(Voucher $voucher, Connection $db, User $user)
    {
        $ts = $this->now();
        $data = [
            'redeemed_by_user_id' => 1,
            'status' => Voucher::STATUS_REDEEMED,
            'redeemed_at' => $ts,
        ];
        $identifier = [
            'code' => 1234567890123456
        ];

        $voucher->getStatus()->shouldBeCalled()->willReturn(Voucher::STATUS_REDEEMED);
        $voucher->getRedeemer()->willReturn($user);
        $user->getId()->shouldBeCalled()->willReturn(1);

        $voucher->getCode()->shouldBeCalled()->willReturn(1234567890123456);

        $voucher->setTimestamp(Argument::any())->shouldBeCalled();

        $db->update('vouchers', $data, $identifier)->shouldBeCalled();

        $this->update($voucher);
    }
}
