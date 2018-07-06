<?php

namespace spec\Btc\CoreBundle\Model;

use Btc\CoreBundle\Entity\Currency;
use Btc\CoreBundle\Entity\Market;
use Btc\CoreBundle\Model\MarketInfo;
use PhpSpec\ObjectBehavior;

class MarketInfoSpec extends ObjectBehavior
{
    public function let(
        Market $market,
        Currency $wCurrency,
        Currency $currency
    ) {
        $wCurrency->getPrecision()->willReturn(8);
        $wCurrency->getSign()->willReturn('$');
        $currency->getPrecision()->willReturn(2);
        $currency->getSign()->willReturn('B');
        $market->getQuotePrecision()->willReturn(2);
        $market->getPricePrecision()->willReturn(5);
        $market->getBasePrecision()->willReturn(8);
        $market->getName()->willReturn('BTC-USD');
        $market->getSlug()->willReturn('btc-usd');
        $market->getWithCurrency()->willReturn($wCurrency);
        $market->getCurrency()->willReturn($currency);
        $this->beConstructedWith($market);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(MarketInfo::class);
    }

    public function it_should_format_number(Currency $currency)
    {
        $currency->getPrecision()->willReturn(2);
        $currency->getSign()->willReturn('$');
        $this->formatNumber(100, $currency, 2)->shouldReturn('$100.00');
        $this->formatNumber(166.1983, $currency, 2)->shouldReturn('$166.19');
        $this->formatNumber(0.6666666, $currency, 2)->shouldReturn('$0.66');
        $this->formatNumber(0.6666666, $currency)->shouldReturn('$0.66');
        $this->formatNumber(0.6666666, $currency, 6)->shouldReturn('$0.666666');
    }

    public function it_should_format_quote_price()
    {
        $this->formatQuote(100)->shouldReturn('$100.00');
        $this->formatQuote(13.131313)->shouldReturn('$13.13');
    }

    public function it_should_format_price()
    {
        $this->formatPrice(100)->shouldReturn('$100.00000');
        $this->formatPrice(13.131313)->shouldReturn('$13.13131');
    }

    public function it_should_format_base_price()
    {
        $this->formatBase(1)->shouldReturn('B1.00000000');
        $this->formatBase(13.131313)->shouldReturn('B13.13131300');
        $this->formatBase(6.66666666666666)->shouldReturn('B6.66666666');
    }

    public function it_should_return_base_currency_object($currency)
    {
        $this->currencyBase()->shouldReturn($currency);
    }

    public function it_should_return_quote_currency_object($wCurrency)
    {
        $this->currencyQuote()->shouldReturn($wCurrency);
    }

    public function it_should_return_currency_pair_seperated_by_slash()
    {
        $this->currencyPair()->shouldReturn('BTC/USD');
    }

    public function it_should_return_slug()
    {
        $this->slug()->shouldReturn('btc-usd');
    }

    public function it_should_be_able_to_json_market_info()
    {
        $jsonified = json_encode([
            'slug' => 'btc-usd',
            'sign' => [
                'base' => 'B',
                'quote' => '$',
            ],
            'precision' => [
                'base' => 8,
                'quote' => 2,
                'price' => 5,
            ],
        ]);
        $this->toJson()->shouldReturn($jsonified);
    }
}
