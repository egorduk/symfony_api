<?php

namespace spec\Btc\TradeBundle\EventListener;

use Btc\Component\Market\Model\Order;
use Btc\CoreBundle\Entity\Currency;
use Btc\CoreBundle\Entity\Market;
use Btc\UserBundle\Events\UserTradeActivityEvent;
use Btc\UserBundle\Events\AccountActivityEvents;
use Btc\CoreBundle\Entity\Wallet;
use Btc\TradeBundle\Twig\Extension\CurrencyExtension;
use Btc\UserBundle\Repository\WalletRepository;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\Translation\TranslatorInterface;

class FlashListenerSpec extends ObjectBehavior
{
    public function getMatchers()
    {
        return [
            'listenEvent' => function ($subject, $event, $value) {
                if (!array_key_exists($event, $subject)) {
                    return false;
                }

                return $subject[$event] == $value;
            },
        ];
    }

    public function let(
        UserTradeActivityEvent $event,
        Order $buyOrder,
        Order $sellOrder,
        FlashBagInterface $flashBag,
        CurrencyExtension $currencyExtension,
        TranslatorInterface $translator,
        WalletRepository $walletRepo,
        Wallet $wallet,
        Currency $currency
    ) {
        $buyOrder->getMarketSlug()->willReturn('btc-usd');
        $buyOrder->getSide()->willReturn(ORDER::SIDE_BUY);
        $sellOrder->getMarketSlug()->willReturn('btc-usd');
        $sellOrder->getSide()->willReturn(ORDER::SIDE_SELL);
        $buyOrder->getInWalletId()->willReturn(1);
        $sellOrder->getInWalletId()->willReturn(2);
        $walletRepo->findOneBy(Argument::any())->willReturn($wallet);
        $wallet->getCurrency()->willReturn($currency);
        $event->getOrder()->willReturn($buyOrder);
        $currency->getCode()->willReturn('BTC');

        // doubles
        $sellOrder->getAskedUnitPrice()->willReturn('');
        $sellOrder->getAmount()->willReturn('');
        $buyOrder->getAskedUnitPrice()->willReturn('');
        $buyOrder->getAmount()->willReturn('');

        // translate double
        $translator->trans(Argument::any(), Argument::any(), Argument::any())
            ->willReturn('');

        $this->beConstructedWith($flashBag, $currencyExtension, $translator, $walletRepo);
    }

    public function it_is_an_event_subscriber()
    {
        $this->shouldImplement('Symfony\Component\EventDispatcher\EventSubscriberInterface');
    }

    public function it_should_bind_to_limit_buy_order_submission_event()
    {
        $this->getSubscribedEvents()->shouldListenEvent(
            AccountActivityEvents::LIMIT_BUY_ORDER, 'flashLimitBuyOrderSubmitted'
        );
    }

    public function it_should_bind_to_limit_sell_order_submission_event()
    {
        $this->getSubscribedEvents()->shouldListenEvent(
            AccountActivityEvents::LIMIT_SELL_ORDER, 'flashLimitSellOrderSubmitted'
        );
    }

    public function it_should_bind_to_market_buy_order_submission_event()
    {
        $this->getSubscribedEvents()->shouldListenEvent(
            AccountActivityEvents::MARKET_BUY_ORDER, 'flashMarketBuyOrderSubmitted'
        );
    }

    public function it_should_bind_to_market_sell_order_submission_event()
    {
        $this->getSubscribedEvents()->shouldListenEvent(
            AccountActivityEvents::MARKET_SELL_ORDER, 'flashMarketSellOrderSubmitted'
        );
    }

    public function its_limit_order_handling_should_ignore_market_orders(UserTradeActivityEvent $event, Order $buyOrder)
    {
        $this->stubMarketOrderSubmission($event, $buyOrder);
        $this->flashLimitBuyOrderSubmitted($event)->shouldBe(false);
    }

    public function its_limit_order_handling_should_process_limit_buy_orders(UserTradeActivityEvent $event, Order $buyOrder)
    {
        $this->stubLimitOrderSubmission($event, $buyOrder);
        $this->flashLimitBuyOrderSubmitted($event)->shouldBe(true);
    }

    public function its_limit_order_handling_should_process_limit_sell_orders(UserTradeActivityEvent $event, Order $sellOrder)
    {
        $this->stubLimitOrderSubmission($event, $sellOrder);
        $this->flashLimitSellOrderSubmitted($event)->shouldBe(true);
    }

    public function its_market_order_handling_should_ignore_limit_orders(UserTradeActivityEvent $event, Order $buyOrder)
    {
        $this->stubLimitOrderSubmission($event, $buyOrder);
        $this->flashMarketBuyOrderSubmitted($event)->shouldBe(false);
    }

    public function its_market_order_handling_should_process_market_buy_orders(UserTradeActivityEvent $event, Order $buyOrder)
    {
        $this->stubMarketOrderSubmission($event, $buyOrder);
        $this->flashMarketBuyOrderSubmitted($event)->shouldBe(true);
    }

    public function its_market_order_handling_should_process_market_sell_orders(UserTradeActivityEvent $event, Order $sellOrder)
    {
        $this->stubMarketOrderSubmission($event, $sellOrder);
        $this->flashMarketSellOrderSubmitted($event)->shouldBe(true);
    }

    public function it_should_add_flash_translated_message_on_limit_buy_order_submission(
        UserTradeActivityEvent $event,
        Order $buyOrder,
        FlashBagInterface $flashBag,
        TranslatorInterface $translator
    ) {
        $this->stubLimitOrderSubmission($event, $buyOrder);

        $translator->trans('flash.success.limit_buy', Argument::any(), 'Trade')
            ->shouldBeCalled()
            ->willReturn('Successful limit buy');

        $flashBag->add('success', 'Successful limit buy')->shouldBeCalled();
        $this->flashLimitBuyOrderSubmitted($event);
    }

    public function it_should_add_flash_translated_message_on_limit_sell_order_submission(
        UserTradeActivityEvent $event,
        Order $sellOrder,
        FlashBagInterface $flashBag,
        TranslatorInterface $translator
    ) {
        $this->stubLimitOrderSubmission($event, $sellOrder);

        $translator->trans('flash.success.limit_sell', Argument::any(), Argument::any())
            ->shouldBeCalled()
            ->willReturn('Successful limit sell');

        $flashBag->add('success', 'Successful limit sell')->shouldBeCalled();

        $this->flashLimitSellOrderSubmitted($event);
    }

    public function it_should_add_flash_translated_message_on_market_buy_order_submission(
        UserTradeActivityEvent $event,
        Order $buyOrder,
        TranslatorInterface $translator,
        FlashBagInterface $flashBag
    ) {
        $this->stubMarketOrderSubmission($event, $buyOrder);

        $translator->trans('flash.success.market_buy', Argument::any(), Argument::any())
            ->shouldBeCalled()
            ->willReturn('Successful instant buy order submission');

        $flashBag->add('success', 'Successful instant buy order submission')
            ->shouldBeCalled();

        $this->flashMarketBuyOrderSubmitted($event);
    }

    public function it_should_add_flash_translated_message_on_market_sell_order_submission(
        UserTradeActivityEvent $event,
        Order $sellOrder,
        TranslatorInterface $translator,
        FlashBagInterface $flashBag
    ) {
        $this->stubMarketOrderSubmission($event, $sellOrder);

        $translator->trans('flash.success.market_sell', Argument::any(), Argument::any())
            ->shouldBeCalled()
            ->willReturn('Successful instant sell order submission');

        $flashBag->add('success', 'Successful instant sell order submission');

        $this->flashMarketSellOrderSubmitted($event);
    }

    public function it_should_format_amounts_depending_on_currency_on_limit_order_submission(
        UserTradeActivityEvent $event,
        Order $buyOrder,
        TranslatorInterface $translator,
        CurrencyExtension $currencyExtension
    ) {
        $ppu = 1;
        $amount = 4;

        $buyOrder->getAskedUnitPrice()->willReturn($ppu);
        $buyOrder->getAmount()->willReturn($amount);

        $this->stubLimitOrderSubmission($event, $buyOrder);
        $this->stubPriceFormat($currencyExtension, $ppu, 'USD');
        $this->stubPriceFormat($currencyExtension, $amount, 'BTC');

        $transArgs = ['%amount%' => '4 BTC', '%unit_price%' => '1 USD'];

        $translator->trans(Argument::any(), $transArgs, Argument::any())->shouldBeCalled();

        $this->flashLimitBuyOrderSubmitted($event);
    }

    public function it_should_format_amount_on_market_buy_order_submission(
        UserTradeActivityEvent $event,
        Order $buyOrder,
        TranslatorInterface $translator,
        CurrencyExtension $currencyExtension
    ) {
        $amount = 1;
        $buyOrder->getAmount()->willReturn($amount);

        $this->stubMarketOrderSubmission($event, $buyOrder);
        $this->stubPriceFormat($currencyExtension, $amount, 'BTC');
        $translator->trans(Argument::any(), ['%amount%' => '1 BTC'], Argument::any())
            ->shouldBeCalled();

        $this->flashMarketBuyOrderSubmitted($event);
    }

    public function it_should_format_amount_on_market_sell_order_submission(
        UserTradeActivityEvent $event,
        Order $sellOrder,
        TranslatorInterface $translator,
        CurrencyExtension $currencyExtension
    ) {
        $amount = 1;
        $sellOrder->getAmount()->willReturn($amount);

        $this->stubMarketOrderSubmission($event, $sellOrder);
        $this->stubPriceFormat($currencyExtension, $amount, 'BTC');
        $translator->trans(Argument::any(), ['%amount%' => '1 BTC', '%currency%' => 'BTC'], Argument::any())
            ->shouldBeCalled();

        $this->flashMarketSellOrderSubmitted($event);
    }

    public function it_should_use_markets_with_currency_on_market_buy_order_submission(
        UserTradeActivityEvent $event,
        Order $buyOrder
    ) {
        $buyOrder->getMarketSlug()->shouldBeCalled();
        $this->stubMarketOrderSubmission($event, $buyOrder);
        $this->flashMarketBuyOrderSubmitted($event);
    }

    public function it_should_use_markets_main_currency_on_market_sell_order_submission(
        UserTradeActivityEvent $event,
        Order $sellOrder
    ) {
        $sellOrder->getMarketSlug()->shouldBeCalled();
        $this->stubMarketOrderSubmission($event, $sellOrder);
        $this->flashMarketSellOrderSubmitted($event);
    }

    /**
     * @param orderSubmissionEvent $event
     * @param $order
     * @param Market $market
     */
    private function stubLimitOrderSubmission(UserTradeActivityEvent $event, Order $order)
    {
        $order->getType()->willReturn(ORDER::TYPE_LIMIT);
        $event->getOrder()->willReturn($order);
    }

    /**
     * @param orderSubmissionEvent $event
     * @param $order
     * @param Market $market
     */
    private function stubMarketOrderSubmission(UserTradeActivityEvent $event, Order $order)
    {
        $order->getType()->willReturn(ORDER::TYPE_MARKET);
        $event->getOrder()->willReturn($order);
    }

    /**
     * @param CurrencyExtension $currencyExtension
     * @param $number
     * @param $currencyCode
     */
    private function stubPriceFormat(CurrencyExtension $currencyExtension, $number, $currencyCode)
    {
        $currencyExtension->priceFilter($number, $currencyCode)->willReturn($number." $currencyCode");
    }
}
