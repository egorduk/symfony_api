<?php

namespace Btc\TradeBundle\Controller;

use Btc\Component\Market\Model\Order;
use Btc\Component\Market\Error\ErrorInterface;
use Btc\CoreBundle\Entity\Market;
use Btc\TradeBundle\Form\Type\MarketOrderType;
use Btc\TradeBundle\Form\Type\LimitOrderType;
use Btc\CoreBundle\Model\UserWallets;

use Btc\UserBundle\Events\AccountActivityEvents;
use Btc\UserBundle\Events\UserTradeActivityEvent;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormError;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;

class OrderController extends Controller
{
    use TradeControllerExtension;

    /**
     * Quick trade
     *
     * @Route("/market", name="btc_trade_order_market")
     * @ParamConverter("market")
     * @Method({"GET", "POST"})
     * @Template
     */
    public function marketAction(Request $request, Market $market)
    {
        list($buyFees, $sellFees) = $this->getFees($market->getId());
        $buy = new Order();
        $buy->setSide(Order::SIDE_BUY);
        $sell = new Order();
        $sell->setSide(Order::SIDE_SELL);
        $buyForm = $this->createDealForm($buy, 'Market', $buyFees);
        $sellForm = $this->createDealForm($sell, 'Market', $sellFees);
        list($topBuyers, $topSellers) = $this->getTopDeals($market);
        // if buy form was submitted
        $buyForm->handleRequest($request);
        if ($buyForm->isValid()) {
            $deal = $buyForm->getData();
            // set wallets
            $this->setWallets($deal);

            $dealService = $this->get('btc_trade.service.deal_submission');
            try {
                if ($err = $dealService->submit($deal)) {
                    // set violations to form
                    $this->setFormError($err, $buyForm);
                } else {
                    $this->get('event_dispatcher')->dispatch(
                        AccountActivityEvents::MARKET_BUY_ORDER,
                        new UserTradeActivityEvent($this->getUser(), $request, $deal)
                    );
                    return $this->redirectBackOrDefault($request, 'btc_trade_order_market');
                }
            } catch (\Exception $e) {
                throw new HttpException(500, $this->get('translator')->trans('order.error.market_buy', [], 'Trade'), $e);
            }
        }

        // if sell form was submitted
        $sellForm->handleRequest($request);
        if ($sellForm->isValid()) {
            $deal = $sellForm->getData();
            // set wallets
            $this->setWallets($deal);

            $dealService = $this->get('btc_trade.service.deal_submission');
            try {
                if ($err = $dealService->submit($deal)) {
                    // set violations to form
                    $this->setFormError($err, $sellForm);
                } else {
                    $this->get('event_dispatcher')->dispatch(
                        AccountActivityEvents::MARKET_SELL_ORDER,
                        new UserTradeActivityEvent($this->getUser(), $request, $deal)
                    );
                    return $this->redirectBackOrDefault($request, 'btc_trade_order_market');
                }
            } catch (\Exception $e) {
                throw new HttpException(500, $this->get('translator')->trans('order.error.market_sell', [], 'Trade'), $e);
            }
        }

        $buyForm = $buyForm->createView();
        $sellForm = $sellForm->createView();

        /* Limit Form */
        $buyFormLimit = $this->createDealForm($buy, 'Limit', $buyFees);
        $sellFormLimit = $this->createDealForm($sell, 'Limit', $sellFees);

        $buyFormLimit->handleRequest($request);
        if ($buyFormLimit->isValid()) {
            $deal = $buyFormLimit->getData();
            // set wallets
            $this->setWallets($deal);

            $dealService = $this->get('btc_trade.service.deal_submission');
            try {
                if ($err = $dealService->submit($deal)) {
                    // set violations to form
                    $this->setFormError($err, $buyFormLimit);
                } else {
                    $this->get('event_dispatcher')->dispatch(
                        AccountActivityEvents::LIMIT_BUY_ORDER,
                        new UserTradeActivityEvent($this->getUser(), $request, $deal)
                    );
                    return $this->redirectBackOrDefault($request, 'btc_trade_order_market');
                }
            } catch (\Exception $e) {
                throw new HttpException(500, $this->get('translator')->trans('order.error.limit_buy', [], 'Trade'), $e);
            }
        }

        // if sell form was submitted
        $sellFormLimit->handleRequest($request);
        if ($sellFormLimit->isValid()) {
            $deal = $sellFormLimit->getData();
            // set wallets
            $this->setWallets($deal);

            $dealService = $this->get('btc_trade.service.deal_submission');
            try {
                if ($err = $dealService->submit($deal)) {
                    // set violations to form
                    $this->setFormError($err, $sellFormLimit);
                } else {
                    $this->get('event_dispatcher')->dispatch(
                        AccountActivityEvents::LIMIT_SELL_ORDER,
                        new UserTradeActivityEvent($this->getUser(), $request, $deal)
                    );
                    return $this->redirectBackOrDefault($request, 'btc_trade_order_market');
                }
            } catch (\Exception $e) {
                throw new HttpException(500, $this->get('translator')->trans('order.error.limit_sell', [], 'Trade'), $e);
            }
        }

        $buyFormLimit = $buyFormLimit->createView();
        $sellFormLimit = $sellFormLimit->createView();

        /* User balance */
        $walletBalance = ['base' => 0, 'quote' => 0];

        list($currencyBase, $currencyQuote) = explode('-', $market->getName());

        $wallets = new UserWallets($this->getUser());
        list($fiat, $crypto) = $wallets->splitAndSortForMarket($market);
        $wallets = array_merge($fiat, $crypto);

        // set user balance for currency pair
        foreach ($wallets as $row) {
            if ($row->getCurrency()->getCode() === $currencyBase) {
                $walletBalance['base'] = $row->getAmountTotal();
            } elseif ($row->getCurrency()->getCode() === $currencyQuote) {
                $walletBalance['quote'] = $row->getAmountTotal();
            }
        }

        /* Currency box */
        try {
            $all = $this->get('btc_frontend.market.grouping')->getMarketListWithLastPrices();
        } catch (\RedisException $e) {
            throw new HttpException(503, "Redis service is temporary unavailable.");
        }
        $markets = array_slice($all, 0, 6);
        $remaining = [];
//        $remaining = count($all) > 6 ? array_slice($all, 6) : [];


        // todo - REMOVE ALL THIS TRASH
        $orderbook = $this->get('btc_frontend.service.orderbook');
        $chart = $this->get('btc_frontend.service.orderbook_chart');
        $bids = $orderbook->getBuyDeals($market->getSlug());
        $asks = $orderbook->getSellDeals($market->getSlug());

        $formatted = $chart->getChartData($bids, $asks, 20);

        $bids = array_slice($bids, 0, 400);
        $asks = array_slice($asks, 0, 400);

        return compact(
            'buyForm',
            'sellForm',
            'topSellers',
            'topBuyers',
            'buyFees',
            'sellFees',
            'buyFormLimit',
            'sellFormLimit',
            'fiat',
            'crypto',
            'wallets',
            'markets',
            'remaining',
            'walletBalance',
            'market',
            'bids',
            'asks',
            'formatted'
        );
    }

    /**
     * Limit Buy / Sell orders
     *
     * @Route("/limit", name="btc_trade_order_limit")
     * @ParamConverter("market")
     * @Method({"GET", "POST"})
     * @Template
     */
    public function limitAction(Market $market, Request $request)
    {
        list($buyFees, $sellFees) = $this->getFees($market->getId());
        $buy = new Order();
        $buy->setSide(Order::SIDE_BUY);
        $sell = new Order();
        $sell->setSide(Order::SIDE_SELL);
        $buyForm = $this->createDealForm($buy, 'Limit', $buyFees);
        $sellForm = $this->createDealForm($sell, 'Limit', $sellFees);
        list($topBuyers, $topSellers) = $this->getTopDeals($market);

        // if buy form was submitted
        $buyForm->handleRequest($request);
        if ($buyForm->isValid()) {
            $deal = $buyForm->getData();
            // set wallets
            $this->setWallets($deal);

            $dealService = $this->get('btc_trade.service.deal_submission');
            try {
                if ($err = $dealService->submit($deal)) {
                    // set violations to form
                    $this->setFormError($err, $buyForm);
                } else {
                    $this->get('event_dispatcher')->dispatch(
                        AccountActivityEvents::LIMIT_BUY_ORDER,
                        new UserTradeActivityEvent($this->getUser(), $request, $deal)
                    );
                    return $this->redirectBackOrDefault($request, 'btc_trade_order_limit');
                }
            } catch (\Exception $e) {
                throw new HttpException(500, $this->get('translator')->trans('order.error.limit_buy', [], 'Trade'), $e);
            }
        }

        // if sell form was submitted
        $sellForm->handleRequest($request);
        if ($sellForm->isValid()) {
            $deal = $sellForm->getData();
            // set wallets
            $this->setWallets($deal);

            $dealService = $this->get('btc_trade.service.deal_submission');
            try {
                if ($err = $dealService->submit($deal)) {
                    // set violations to form
                    $this->setFormError($err, $sellForm);
                } else {
                    $this->get('event_dispatcher')->dispatch(
                        AccountActivityEvents::LIMIT_SELL_ORDER,
                        new UserTradeActivityEvent($this->getUser(), $request, $deal)
                    );
                    return $this->redirectBackOrDefault($request, 'btc_trade_order_limit');
                }
            } catch (\Exception $e) {
                throw new HttpException(500, $this->get('translator')->trans('order.error.limit_sell', [], 'Trade'), $e);
            }
        }

        $buyForm = $buyForm->createView();
        $sellForm = $sellForm->createView();

        return compact(
            'buyForm',
            'sellForm',
            'topBuyers',
            'topSellers',
            'buyFees',
            'sellFees'
        );
    }

    private function createDealForm(Order $deal, $type, array $fees)
    {
        $market = $this->getRequest()->attributes->get('_market'); // the market is always loaded into internal attribute
        $options = [
            'validation_groups' => $type,
            'csrf_protection' => false
        ];
        $form = null;

        // create form
        if ($type === 'Limit') {
            $deal->setType(Order::TYPE_LIMIT);
            if ($deal->getSide() === Order::SIDE_BUY) {
                $form = $this->createForm(new LimitOrderType(Order::SIDE_BUY, get_class($deal)), $deal, $options);
            } else {
                $form = $this->createForm(new LimitOrderType(Order::SIDE_SELL, get_class($deal)), $deal, $options);
            }
        } elseif ($type === 'Market') {
            $deal->setType(Order::TYPE_MARKET);
            if ($deal->getSide() === Order::SIDE_BUY) {
                $form = $this->createForm(new MarketOrderType(Order::SIDE_BUY, get_class($deal)), $deal, $options);
            } else {
                $form = $this->createForm(new MarketOrderType(Order::SIDE_SELL, get_class($deal)), $deal, $options);
            }
        }

        // fees
        $deal->setFeePercent($fees['percent']);

        return $form;
    }

    private function getTopDeals(Market $market, $limit = 8)
    {
        $orderbook = $this->get('btc_frontend.service.orderbook');

        return [
            $orderbook->getBuyDeals($market->getSlug(), $limit),
            $orderbook->getSellDeals($market->getSlug(), $limit)
        ];
    }

    private function setFormError(ErrorInterface $error, Form $form)
    {
        $msg = $error->message($this->get('translator'));
        $form->get("amount")->addError(new FormError($msg));
    }

    /**
     * Checks if _redirect is present and we need to redirect back.
     *
     * If redirect is not present then we redirect to default route
     *
     * @param Request $request
     * @param $defaultRoute
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    protected function redirectBackOrDefault(Request $request, $defaultRoute)
    {
        $url = $request->get('_redirect', false) ? : $this->generateUrl($defaultRoute);
        return new RedirectResponse($url);
    }

    private function getFees($marketId)
    {
        $market = $this->get('rest.service.market')->get($marketId);

        $feeService = $this->get('btc_trade.service.fee_service');
        $feeSet = $feeService->getFeeSet($this->getUser(), $market);

        list($fixedBuy, $percentBuy) = $feeSet->getBuyFeeByMarket($marketId);
        list($fixedSell, $percentSell) = $feeSet->getSellFeeByMarket($marketId);

        return [
            ['fixed' => $fixedBuy, 'percent' => $percentBuy],
            ['fixed' => $fixedSell, 'percent' => $percentSell]
        ];
    }

    /**
     * @param Order $deal
     */
    private function setWallets(Order $deal)
    {
        $this->setServiceWallets($this->get('btc_user.repository.wallet'));
        $this->setServiceUser($this->getUser());

        $market = $this->getRequest()->attributes->get('_market');

        if ($deal->getSide() === Order::SIDE_SELL) {
            $deal->setInWalletId($this->findWalletOr500($market->getWithCurrency())->getId());
            $deal->setOutWalletId($this->findWalletOr500($market->getCurrency())->getId());
        } else {
            $deal->setInWalletId($this->findWalletOr500($market->getCurrency())->getId());
            $deal->setOutWalletId($this->findWalletOr500($market->getWithCurrency())->getId());
        }
        // market
        $deal->setMarketSlug($market->getSlug());
        $deal->setMarketId($market->getId());
    }
}