<?php

namespace Btc\FrontendApiBundle\Controller;

use Btc\FrontendApiBundle\Exception\Rest\NotFoundException;
use FOS\RestBundle\Controller\Annotations;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Request\ParamFetcherInterface;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class MarketController extends FOSRestController
{
    /**
     * Gets all markets.
     *
     * Gets general information: currencies and markets.
     *
     * ### Request URL example ###
     * GET /api/v1/markets?offset=0&limit=10000
     * ### Success response example ###
     *     {
     *       "markets": [{
     *         "id": 2,
     *         "slug": "btc-usd",
     *         "currency": {
     *           "id": 2,
     *           "code": "BTC",
     *           "sign": "฿",
     *           "format": 8,
     *           "crypto": true,
     *           "eth": false,
     *           "is_erc_token": false,
     *           "contract_address": "",
     *           "contract_abi": ""
     *         },
     *         "with_currency": {
     *           "id": 1,
     *           "code": "USD",
     *           "sign": "$",
     *           "format": 2,
     *           "crypto": false
     *         },
     *         "name": "BTC-USD",
     *         "internal": false,
     *         "base_precision": 8,
     *         "quote_precision": 2,
     *         "price_precision": 5
     *         "settings": [{
     *           "id": 44,
     *           "slug": "bfx-bid-price-margin",
     *           "name": "BFX BID price margin",
     *           "value": "0.25",
     *           "description": "Bitfinex BID price margin percent"
     *         }]
     *       }]
     *     }
     * ### Error response example ###
     *     {
     *       "status": 404,
     *       "error": "NOT_FOUND"
     *     }
     *
     * @ApiDoc(
     *   resource = true,
     *   description = "Gets all markets",
     *   output = "Btc\CoreBundle\Entity\Market",
     *   statusCodes = {
     *     Response::HTTP_OK = "Returned when successful",
     *     Response::HTTP_NOT_FOUND = "Returned when the data are not found"
     *   },
     *   section = "Market"
     * )
     *
     * @Annotations\QueryParam(name="offset", requirements="\d+", nullable=true, default="0", description="Offset from which to start listing items.")
     * @Annotations\QueryParam(name="limit", requirements="\d+", default="10000", description="How many items to return.")
     *
     * @param ParamFetcherInterface $paramFetcher param fetcher service
     *
     * @return Response
     *
     * @throws NotFoundHttpException when items do not exist
     */
    public function getMarketsAction(ParamFetcherInterface $paramFetcher)
    {
        $offset = $paramFetcher->get('offset');
        $offset = null === $offset ? 0 : $offset;
        $limit = $paramFetcher->get('limit');

        if ($markets = $this->get('rest.service.market')->all($limit, $offset)) {
            return $this->handleView(
                $this->view([
                    'markets' => $markets,
                ], Response::HTTP_OK)
            );
        }

        throw new NotFoundException();
    }

    /**
     * Gets market by slug.
     *
     * ### Request URL example ###
     * GET /api/v1/markets/slug/btc-usd
     * ### Success response example ###
     *     {
     *       "markets": [{
     *         "id": 2,
     *         "slug": "btc-usd",
     *         "currency": {
     *           "id": 2,
     *           "code": "BTC",
     *           "sign": "฿",
     *           "format": 8,
     *           "crypto": true,
     *           "eth": false,
     *           "is_erc_token": false,
     *           "contract_address": "",
     *           "contract_abi": ""
     *         },
     *         "with_currency": {
     *           "id": 1,
     *           "code": "USD",
     *           "sign": "$",
     *           "format": 2,
     *           "crypto": false
     *         },
     *         "name": "BTC-USD",
     *         "internal": false,
     *         "base_precision": 8,
     *         "quote_precision": 2,
     *         "price_precision": 5
     *         "settings": [{
     *           "id": 44,
     *           "slug": "bfx-bid-price-margin",
     *           "name": "BFX BID price margin",
     *           "value": "0.25",
     *           "description": "Bitfinex BID price margin percent"
     *         }]
     *       }]
     *     }
     * ### Error response example ###
     *     {
     *       "status": 404,
     *       "error": "NOT_FOUND"
     *     }
     *     {
     *       "status": 400,
     *       "error": "INCORRECT_DATA"
     *     }
     *
     * @ApiDoc(
     *   resource = true,
     *   description = "Gets market by slug",
     *   output = "Btc\CoreBundle\Entity\Market",
     *   statusCodes = {
     *     Response::HTTP_OK = "Returned when successful",
     *     Response::HTTP_BAD_REQUEST = "Returned when inputted incorrect data",
     *     Response::HTTP_NOT_FOUND = "Returned when the data are not found"
     *   },
     *   section = "Market"
     * )
     *
     * @Annotations\Get("/markets/slug/{marketSlug}", requirements = { "marketSlug" = "\S+" })
     *
     * @param string $marketSlug
     *
     * @return Response
     *
     * @throws NotFoundException when item do not exist
     */
    public function getMarketBySlugAction($marketSlug)
    {
        if (!$market = $this->get('rest.service.market')->getOneBy(['slug' => $marketSlug])) {
            throw new NotFoundException();
        }

        return $this->handleView(
            $this->view([
                'market' => $market,
            ], Response::HTTP_OK)
        );
    }

    /**
     * Gets latest market prices.
     *
     * ### Request URL example ###
     * GET /api/v1/markets/prices
     * ### Success response example ###
     *     {
     *       "data": [{
     *          "id": 1,
     *          "price": 13972.98
     *       }]
     *     }
     *
     * @ApiDoc(
     *   resource = true,
     *   description = "Gets latest market prices",
     *   statusCodes = {
     *     Response::HTTP_OK = "Returned when successful",
     *   },
     *   section = "Market"
     * )
     *
     * @Annotations\Get("/markets/prices")
     *
     * @return Response
     */
    public function getMarketPricesAction()
    {
        $data = [];

        $markets = $this->get('rest.service.market_grouping')
            ->getMarketListWithLastPrices();

        foreach ($markets as $market) {
            $data[] = [
                'id' => $market['info']->marketId(),
                'price' => $market['price'],
            ];
        }

        return $this->handleView(
            $this->view([
                'data' => $data,
            ], Response::HTTP_OK)
        );
    }
}
