<?php

namespace spec\Btc\FrontendApiBundle\Events;

use Btc\Component\Market\Model\Order;
use Btc\CoreBundle\Entity\User;
use PhpSpec\ObjectBehavior;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\HttpFoundation\Request;

class UserTradeActivityEventSpec extends ObjectBehavior
{
    const CURRENCY_FAKE = 'BTC';
    const AMOUNT_FAKE = '1';
    const RESULT_FAKE = '1 BTC';

    public function let(User $user, Request $request, Order $order)
    {
        $this->beConstructedWith($user, $request, $order);
    }

    public function it_is_an_event()
    {
        $this->shouldHaveType(Event::class);
    }

    public function it_should_return_order_passed_through_construct(Order $order)
    {
        $this->getOrder()->shouldBe($order);
    }

    public function it_should_return_amount_with_currency(Order $order)
    {
        $order->getAssetCurrencyCode()->willReturn(self::CURRENCY_FAKE);
        $order->getAmount()->willReturn(self::AMOUNT_FAKE);

        $this->getAmountWithCurrency()->shouldBe(self::RESULT_FAKE);
    }

    public function it_should_return_price_with_currency(Order $order)
    {
        $order->getFundsCurrencyCode()->willReturn(self::CURRENCY_FAKE);
        $order->getAskedUnitPrice()->willReturn(self::AMOUNT_FAKE);

        $this->getPriceWithCurrency()->shouldBe(self::RESULT_FAKE);
    }
}
