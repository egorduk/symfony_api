<?php

namespace spec\Btc\Component\Market\Model;

use Btc\Component\Market\Model\Voucher;
use Btc\FrontendApiBundle\Classes\SpecValidatorTrait;
use PhpSpec\ObjectBehavior;

class VoucherSpec extends ObjectBehavior
{
    use SpecValidatorTrait;

    function let()
    {
        $this->initValidator();
    }

    function it_is_initializable()
    {
        $this->shouldHaveType(Voucher::class);
    }

    function it_should_not_allow_amount_to_be_blank()
    {
        $voucher = new Voucher;
        $voucher->setAmount(null);

        $this->shouldHaveViolation(
            $this->validator->validate($voucher, ['Voucher']),
            'voucher.amount.blank'
        );
    }

    function it_should_not_allow_amount_to_be_zero()
    {
        $voucher = new Voucher;
        $voucher->setAmount(0);

        $this->shouldHaveViolation(
            $this->validator->validate($voucher, ['Voucher']),
            'voucher.amount.zero_or_negative'
        );
    }

    function it_should_not_allow_price_to_be_negative()
    {
        $voucher = new Voucher;
        $voucher->setAmount(-5.8);

        $this->shouldHaveViolation(
            $this->validator->validate($voucher, ['Voucher']),
            'voucher.amount.zero_or_negative'
        );
    }
}
