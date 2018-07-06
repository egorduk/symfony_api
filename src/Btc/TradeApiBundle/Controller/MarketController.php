<?php

namespace Btc\TradeApiBundle\Controller;

use Btc\FrontendApiBundle\Exception\Rest\NotFoundException;
use Btc\FrontendApiBundle\Exception\Rest\UnknownErrorException;
use Btc\TradeApiBundle\Presenter\MergedOrderBook;
use Btc\TradeApiBundle\Presenter\Ticker;
use FOS\RestBundle\Controller\Annotations;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Request\ParamFetcherInterface;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class MarketController extends FOSRestController
{
    /**
     * Gets live market stats, 24 hour volume, top order prices, highest and lowest prices for last 24 hours.
     *
     * ### Request URL example ###
     * GET /api/trade/v1/market/ticker?market=btc-usd
     * ### Success response example ###
     *     {
     *       "last": "",
     *       "high": "",
     *       "low": "",
     *       "volume": "",
     *       "bid": "",
     *       "ask": ""
     *     }
     * ### Error response example ###
     *     {
     *       "status": 404,
     *       "error": "NOT_FOUND"
     *     }
     *
     * @ApiDoc(
     *   resource = true,
     *   description = "Gets live market stats, 24 hour volume, top order prices, highest and lowest prices for last 24 hours",
     *   statusCodes = {
     *     Response::HTTP_OK = "Returned when successful",
     *     Response::HTTP_NOT_FOUND = "Returned when the data are not found"
     *   },
     *   section = "Trade"
     * )
     *
     * @Annotations\Get("/market/ticker/{market}", requirements = { "market" = "\S+" })
     *
     * @param string $market
     *
     * @return Response
     *
     * @throws NotFoundHttpException when data do not exist
     */
    public function getMarketTickerAction($market)
    {
        if (!$market = $this->get('rest.service.market')->getOneBy(['slug' => $market])) {
            throw new NotFoundException();
        }

        $redis = $this->get('rest.redis');

        $keys = [
            Ticker::TX_LAST_PRICE,
            Ticker::TX_VOLUME_24,
            Ticker::TX_VOLUME_CRYPTO24,
            Ticker::TX_HIGH_24,
            Ticker::TX_LOW_24,
        ];

        try {
            $data = $redis->mGet(array_map(function($key) use($market) {
                return $key . '.' . $market->getSlug();
            }, $keys));
        } catch (\RedisException $e) {
            throw new UnknownErrorException();
        }

        $orderBookService = $this->get('rest.service.order_book');

        $keys[] = Ticker::LOWEST_ASK;
        $keys[] = Ticker::HIGHEST_BID;
        $data[Ticker::LOWEST_ASK] = $orderBookService->getLowestAskPrice($market->getSlug());
        $data[Ticker::HIGHEST_BID] = $orderBookService->getHighestBidPrice($market->getSlug());

        $ticker = new Ticker(array_combine($keys, $data));

        return $this->handleView(
            $this->view($ticker->presentAsJson(), Response::HTTP_OK)
        );
    }

    /**
     * Gets live order book.
     *
     * ### Request URL example ###
     * GET /api/trade/v1/market/order-book?market=btc-usd&limit=10
     * ### Success response example ###
     *     {
     *       "bids": [
     *          {
     *              "amount": "",
     *              "price": ""
     *          }
     *       ],
     *       "asks": [
     *          {
     *              "amount": "",
     *              "price": ""
     *          }
     *       ]
     *     }
     * ### Error response example ###
     *     {
     *       "status": 404,
     *       "error": "NOT_FOUND"
     *     }
     *
     * @ApiDoc(
     *   resource = true,
     *   description = "Gets live order book",
     *   statusCodes = {
     *     Response::HTTP_OK = "Returned when successful",
     *     Response::HTTP_NOT_FOUND = "Returned when the data are not found"
     *   },
     *   section = "Trade"
     * )
     *
     * @Annotations\Get("/market/order-book")
     *
     * @Annotations\QueryParam(name="market", requirements="\S+", description="Market code name.")
     * @Annotations\QueryParam(name="limit", requirements="\d+", default="10", description="How many items to return.")
     *
     * @param ParamFetcherInterface $paramFetcher
     *
     * @return Response
     */
    public function getMarketOrderBookAction(ParamFetcherInterface $paramFetcher)
    {
        $market = $paramFetcher->get('market');
        $limit = $paramFetcher->get('limit');

        if (!$market = $this->get('rest.service.market')->getOneBy(['slug' => $market])) {
            throw new NotFoundException();
        }

        $orderBookService = $this->get('rest.service.order_book');

        $data = new MergedOrderBook(
            $orderBookService->getBuyDeals($market->getSlug(), $limit),
            $orderBookService->getSellDeals($market->getSlug(), $limit)
        );

        return $this->handleView(
            $this->view($data->presentAsJson(), Response::HTTP_OK)
        );
    }
}
