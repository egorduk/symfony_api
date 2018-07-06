<?php

namespace Btc\TradeBundle\EventListener;

use Btc\Component\Market\Model\Order;
use Btc\UserBundle\Events\UserTradeActivityEvent;
use Btc\UserBundle\Events\AccountActivityEvents;
use Btc\TradeBundle\Twig\Extension\CurrencyExtension;
use Btc\UserBundle\Repository\WalletRepository;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\Translation\TranslatorInterface;

class FlashListener implements EventSubscriberInterface
{
    /**
     * @var \Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface
     */
    private $flashes;

    /**
     * @var \Btc\TradeBundle\Twig\Extension\CurrencyExtension
     */
    private $currencyExtension;

    /**
     * @var \Symfony\Component\Translation\TranslatorInterface
     */
    private $translator;

    private $walletRepo;

    public function __construct(
        FlashBagInterface $flashes,
        CurrencyExtension $currencyExtension,
        TranslatorInterface $translator,
        WalletRepository $walletRepo
    ) {

        $this->flashes = $flashes;
        $this->currencyExtension = $currencyExtension;
        $this->translator = $translator;
        $this->walletRepo = $walletRepo;
    }

    /**
     * @return array The event names to listen to
     *
     * @api
     */
    public static function getSubscribedEvents()
    {
        return [
            AccountActivityEvents::LIMIT_BUY_ORDER => 'flashLimitBuyOrderSubmitted',
            AccountActivityEvents::LIMIT_SELL_ORDER => 'flashLimitSellOrderSubmitted',
            AccountActivityEvents::MARKET_BUY_ORDER => 'flashMarketBuyOrderSubmitted',
            AccountActivityEvents::MARKET_SELL_ORDER => 'flashMarketSellOrderSubmitted',
        ];
    }

    public function flashMarketBuyOrderSubmitted(UserTradeActivityEvent $event)
    {
        $deal = $event->getOrder();
        if ($deal->getType() !== ORDER::TYPE_MARKET) {
            return false;
        }
        list($virtual, $fiat) = $this->currencyCodes($deal->getMarketSlug());

        $amount = $this->currencyExtension->priceFilter($deal->getAmount(), $virtual);
        $inWallet = $this->walletRepo->findOneBy(['id' => $deal->getInWalletId()]);


        $message = $this->translator->trans(
            'flash.success.market_buy',
            [
                '%amount%' => $amount,
            ],
            'Trade'
        );

        $this->flashes->add('success', $message);

        return true;
    }

    public function flashMarketSellOrderSubmitted(UserTradeActivityEvent $event)
    {
        $deal = $event->getOrder();
        if ($deal->getType() !== ORDER::TYPE_MARKET) {
            return false;
        }
        list($virtual, $fiat) = $this->currencyCodes($deal->getMarketSlug());

        $amount = $this->currencyExtension->priceFilter($deal->getAmount(), $virtual);
        $inWallet = $this->walletRepo->findOneBy(['id' => $deal->getInWalletId()]);

        $message = $this->translator->trans(
            'flash.success.market_sell',
            [
                '%amount%' => $amount,
                '%currency%' => $inWallet->getCurrency()->getCode()
            ],
            'Trade'
        );

        $this->flashes->add('success', $message);

        return true;
    }

    public function flashLimitSellOrderSubmitted(UserTradeActivityEvent $event)
    {
        $deal = $event->getOrder();
        if ($deal->getType() !== ORDER::TYPE_LIMIT) {
            return false;
        }
        list($virtual, $fiat) = $this->currencyCodes($deal->getMarketSlug());

        $amount = $this->currencyExtension->priceFilter($deal->getAmount(), $virtual);
        $price = $this->currencyExtension->priceFilter($deal->getAskedUnitPrice(), $fiat);
        $message = $this->translator->trans(
            'flash.success.limit_sell',
            ['%amount%' => $amount, '%unit_price%' => $price],
            'Trade'
        );

        $this->flashes->add('success', $message);

        return true;
    }

    public function flashLimitBuyOrderSubmitted(UserTradeActivityEvent $event)
    {
        $deal = $event->getOrder();
        if ($deal->getType() !== ORDER::TYPE_LIMIT) {
            return false;
        }
        list($virtual, $fiat) = $this->currencyCodes($deal->getMarketSlug());

        $amount = $this->currencyExtension->priceFilter($deal->getAmount(), $virtual);
        $price = $this->currencyExtension->priceFilter($deal->getAskedUnitPrice(), $fiat);
        $message = $this->translator->trans(
            'flash.success.limit_buy',
            ['%amount%' => $amount, '%unit_price%' => $price],
            'Trade'
        );

        $this->flashes->add('success', $message);

        return true;
    }

    private function currencyCodes($slug)
    {
        return array_map('strtoupper', explode('-', $slug));
    }
}
