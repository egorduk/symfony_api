<?php

namespace Btc\FrontendApiBundle\Controller;

use Btc\FrontendBundle\Message\PriceNotificationUnsubscribeMessage;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Btc\CoreBundle\Entity\Market;
use Btc\CoreBundle\Entity\PriceNotification;
use Btc\FrontendBundle\Message\PriceNotificationMessage;
use Symfony\Component\HttpKernel\Exception\HttpException;

class DefaultController extends Controller
{
    private $stats = [
        'last.price',
        'volume24.crypto',
        'volume24',
        'high24',
        'low24',
        'today.open',
        'days.high',
        'days.low',
        'average24',
        'average48',
    ];

    private $featuredMarkets = array('btc-usd', 'btc-eur', 'eth-usd', 'eth-eur', 'ltc-usd', 'bnk-usd');

    /**
     * @Route("/", name="btc_frontend_homepage")
     * @Method({"GET", "POST"})
     * @ParamConverter("market")
     * @Template("BtcFrontendBundle:Default:index.html.twig")
     */
    public function homeAction(Request $request, Market $market)
    {
        return $this->redirect('/order/market');

        // @todo cleanup
        $notification = new PriceNotification();
        $redis = $this->get('cache.app'); // app cache on dev env is flushed on every request
        $clientIp = $request->getClientIp();
        $priceNotificationCount = $redis->contains('price-notification-ip-'.$clientIp) ?
            $redis->fetch('price-notification-ip-'.$clientIp) : 0;
        $priceNotificationForm = $this->createPriceNotificationForm($notification, $priceNotificationCount);
        $priceNotificationForm->handleRequest($request);
        if ($priceNotificationForm->isValid()) {
            $redis->save('price-notification-ip-'.$clientIp, ++$priceNotificationCount, 3600);
            $notification->generateHash();
            $notification->setCurrentPrice(floatval($this->get('cache.ticker.connection')->get('last.price.'.$market->getSlug())));
            $em = $this->getDoctrine()->getManager();
            $em->persist($notification);
            $em->flush();

            $this->get('session')->getFlashBag()->add('success', $this->get('translator')->trans('price_notification.flash.success', [
                '%price%' => $this->get('currency')->priceFilter($notification->getPrice(), $notification->getMarket()->getWithCurrency()),
                '%email%' => $notification->getEmail(),
            ], 'Price'));

            // reset price notification form
            $priceNotificationForm = $this->createPriceNotificationForm(new PriceNotification(), $priceNotificationCount);

            // send nsq message
            $this->get('nsq')->send(new PriceNotificationMessage($notification));
        }

        $orderbook = $this->get('btc_frontend.service.orderbook');
        $bids = $orderbook->getBuyDeals($market->getSlug(), 8);
        $asks = $orderbook->getSellDeals($market->getSlug(), 8);

        // stats
        $redis = $this->get('cache.ticker.connection');
        // build cache keys
        $keys = array_map(function ($key) use ($market) {
            return $key.'.'.$market->getSlug();
        }, $this->stats);
        $stats = array_combine($this->stats, array_map('floatval', $redis->mGet($keys)));
        $stats['price'] = floatval($stats['last.price']);
        // calc diff
        $stats['daily.change'] = floatval(bcsub($stats['average24'], $stats['average48'], 8));
        $stats['daily.diff'] = $stats['average48'] > 0 ?
            floatval(bcdiv(bcmul($stats['daily.change'], 100, 8), $stats['average48'], 2)) : 0;
        $stats['current.diff'] = $stats['today.open'] > 0 ?
            floatval(bcdiv(bcsub($stats['price'], $stats['today.open'], 6), $stats['today.open'], 4)) * 100 : 0;

        // chart data
        $chart = json_encode(
            $this->get('transactions')->getYearlyTransactionPriceGroupedByDay(
                $market,
                $this->container->getParameter('data_sources_dir')
            )
        );
        $form = $priceNotificationForm->createView();

        return compact('bids', 'asks', 'market', 'stats', 'chart', 'form');
    }

    /**
     * Rendered as fragment "Market switcher" - selection list.
     *
     * @Template("BtcFrontendBundle:widgets:currency_box.html.twig")
     */
    public function currenciesAction()
    {
        try {
            $all = $this->get('btc_frontend.market.grouping')->getMarketListWithLastPrices();
        } catch (\RedisException $e) {
            throw new HttpException(503, 'Redis service is temporary unavailable.');
        }
        $markets = array_slice($all, 0, 6);
        $remaining = count($all) > 6 ? array_slice($all, 6) : [];

        return compact('markets', 'remaining');
    }

    /**
     * Rendered as fragment "Header price".
     *
     * @Template("BtcFrontendBundle:widgets:header_price.html.twig")
     */
    public function headerAction()
    {
        try {
            $all = $this->get('btc_frontend.market.grouping')->getMarketListWithLastPrices();
        } catch (\RedisException $e) {
            throw new HttpException(503, 'Redis service is temporary unavailable.');
        }

        $markets = array_filter($all, function ($var) {
            return in_array($var['info']->slug(), $this->featuredMarkets);
        });

        return compact('markets');
    }

    private function createPriceNotificationForm(PriceNotification $notification, $priceNotificationCount)
    {
        if ($this->getUser() && $this->getRequest()->getMethod() !== 'POST') {
            $notification->setEmail($this->getUser()->getEmail());
        }
        $form = $this->createForm(new PriceNotificationType($this->get('btc_frontend.market.grouping')), $notification);
        if ($priceNotificationCount > 4) {
            $form->add('captcha', 'captcha', [
                    'width' => 165,
                ]);
        }

        return $form;
    }

    /**
     * @Route("/unsubscribe/{target}/{hash}", name="btc_cancel")
     * @Method({"GET"})
     */
    public function unsubscribeAction($target, $hash)
    {
        switch ($target) {
            case 'price-notification':
                return $this->unsubscribePriceNotification($hash);
            default:
                $this->get('session')->getFlashBag()->add('danger', $this->get('translator')->trans('flash.unsubscribe.invalid', [], 'Price'));

                return $this->redirect($this->generateUrl('btc_frontend_homepage'));
        }
    }

    private function unsubscribePriceNotification($hash)
    {
        $priceNotificationRepo = $this->getDoctrine()->getRepository('BtcCoreBundle:PriceNotification');
        $email = $priceNotificationRepo->findEmailByHash($hash);
        $count = $priceNotificationRepo->cancelSubscriptionsForEmail($email);
        if (intval($count) === 0) {
            $this->get('session')->getFlashBag()->add('danger', $this->get('translator')->trans('price_notification.flash.not_found', [], 'Price'));

            return $this->redirect($this->generateUrl('btc_frontend_homepage'));
        }
        $this->get('session')->getFlashBag()->add('success', $this->get('translator')->trans('price_notification.flash.unsubscribe', [], 'Price'));
        $this->get('nsq')->send(new PriceNotificationUnsubscribeMessage($email));

        return $this->redirect($this->generateUrl('btc_frontend_homepage'));
    }
}
