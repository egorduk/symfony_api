<?php

namespace spec\Btc\Component\Market\Model;

use Btc\Component\Market\Model\Order;
use Btc\CoreBundle\Entity\RestEntityInterface;
use Btc\FrontendApiBundle\Classes\SpecValidatorTrait;
use Exmarkets\NsqBundle\Model\OrderInterface;
use PhpSpec\ObjectBehavior;

class OrderSpec extends ObjectBehavior
{
    use SpecValidatorTrait;

    function let()
    {
        $this->initValidator();
    }

    function it_is_initializable()
    {
        $this->shouldHaveType(Order::class);
        $this->shouldImplement(OrderInterface::class);
        $this->shouldImplement(RestEntityInterface::class);
    }

    function it_should_give_asset_currency_code()
    {
        $this->setMarketSlug('btc-usd');
        $this->getAssetCurrencyCode()->shouldBe('BTC');
    }

    function it_should_give_funds_currency_code()
    {
        $this->setMarketSlug('btc-usd');
        $this->getFundsCurrencyCode()->shouldBe('USD');
    }

    function it_should_give_empty_asset_currency_code_if_market_slug_is_not_set()
    {
        $this->getAssetCurrencyCode()->shouldBe('');
    }

    function it_should_not_allow_amount_to_be_blank_on_limit()
    {
        $deal = new Order();
        $deal->setAmount(null);

        $this->shouldHaveViolation(
            $this->validator->validate($deal, ['Limit']),
            'order.amount.blank'
        );
    }

    function it_should_not_allow_price_to_be_blank_on_limit()
    {
        $deal = new Order();
        $deal->setAskedUnitPrice(null);

        $this->shouldHaveViolation(
            $this->validator->validate($deal, ['Limit']),
            'order.price.blank'
        );
    }

    function it_should_not_allow_amount_to_be_zero_on_limit()
    {
        $deal = new Order();
        $deal->setAmount(0);

        $this->shouldHaveViolation(
            $this->validator->validate($deal, ['Limit']),
            'order.amount.zero_or_negative'
        );
    }

    function it_should_not_allow_price_to_be_zero_on_limit()
    {
        $deal = new Order();
        $deal->setAskedUnitPrice(0);

        $this->shouldHaveViolation(
            $this->validator->validate($deal, ['Limit']),
            'order.price.zero_or_negative'
        );
    }

    function it_should_not_allow_amount_to_be_negative_on_limit()
    {
        $deal = new Order();
        $deal->setAmount(-5.8);

        $this->shouldHaveViolation(
            $this->validator->validate($deal, ['Limit']),
            'order.amount.zero_or_negative'
        );
    }

    function it_should_not_allow_price_to_be_negative_on_limit()
    {
        $deal = new Order();
        $deal->setAmount(-5.8);

        $this->shouldHaveViolation(
            $this->validator->validate($deal, ['Limit']),
            'order.price.zero_or_negative'
        );
    }

    function it_should_not_allow_amount_to_be_blank_on_market()
    {
        $deal = new Order();
        $deal->setAmount(null);

        $this->shouldHaveViolation(
            $this->validator->validate($deal, ['Market']),
            'order.amount.blank'
        );
    }

    function it_should_not_allow_amount_to_be_zero_on_market()
    {
        $deal = new Order();
        $deal->setAmount(0);

        $this->shouldHaveViolation(
            $this->validator->validate($deal, ['Market']),
            'order.amount.zero_or_negative'
        );
    }

    function it_should_not_allow_amount_to_spend_to_be_negative_on_market()
    {
        $deal = new Order();
        $deal->setAmount(-600);

        $this->shouldHaveViolation(
            $this->validator->validate($deal, ['Market']),
            'order.amount.zero_or_negative'
        );
    }
}
