<?php

namespace Btc\FrontendApiBundle\Controller;

use Btc\CoreBundle\Entity\Currency;
use Btc\CoreBundle\Entity\Market;
use Btc\FrontendApiBundle\Exception\Rest\NotFoundException;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Controller\Annotations;
use JMS\Serializer\SerializationContext;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Symfony\Component\HttpFoundation\Response;

class GeneralController extends FOSRestController
{
    /**
     * Gets general information: currencies and markets.
     *
     * ### Request URL example ###
     * GET /api/v1/general/info
     * ### Success response example ###
     *     {
     *       "currencies": {
     *         "id": 2,
     *         "code": "BTC",
     *         "sign": "฿",
     *         "format": 8,
     *         "crypto": true
     *       }
     *       "markets": {
     *         "id": 2,
     *         "slug": "btc-usd",
     *         "currency": {
     *           "id": 2,
     *           "code": "BTC",
     *           "sign": "฿",
     *           "format": 8,
     *           "crypto": true
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
     *         "price_precision": 5,
     *         "last_price": 13972.98000000,
     *         "today_open_price": 13965.00000000,
     *         "volume24": 39.00000000,
     *         "min_amount": 0.2
     *       }
     *     }
     * ### Error response example ###
     *     {
     *       "status": 404,
     *       "error": "NOT_FOUND"
     *     }
     *
     * @ApiDoc(
     *   resource = true,
     *   description = "Gets general information: currencies and markets",
     *   statusCodes = {
     *     Response::HTTP_OK = "Returned when successful",
     *     Response::HTTP_NOT_FOUND = "Returned when the data are not found"
     *   },
     *   section = "General"
     * )
     *
     * @Annotations\Get("/general/info")
     *
     * @return Response
     *
     * @throws NotFoundException when items do not exist
     */
    public function getCurrenciesAndMarketsAction()
    {
        $redis = $this->get('rest.redis');

        $currenciesList = $this->get('rest.service.currency')->all(null);
        $marketsList = $this->get('rest.service.market')->all(null);

        if (empty($currenciesList) && empty($marketsList)) {
            throw new NotFoundException();
        }

        foreach ($marketsList as $market) {
            $market->setLastPrice($redis->get('last.price.'.$market->getSlug()));
            $market->setTodayOpenPrice($redis->get('today.open.'.$market->getSlug()));
            $market->setVolume24($redis->get('volume24.crypto.'.$market->getSlug()));
        }

        $apiVersion = $this->container->getParameter('rest_api_version');

        $serializedData = $this->get('jms_serializer')->serialize($currenciesList, 'json', SerializationContext::create()->setVersion($apiVersion));
        $currencies = $this->get('jms_serializer')->deserialize($serializedData, 'array<'.Currency::class.'>', 'json');
        $serializedData = $this->get('jms_serializer')->serialize($marketsList, 'json', SerializationContext::create()->setVersion($apiVersion));
        $markets = $this->get('jms_serializer')->deserialize($serializedData, 'array<'.Market::class.'>', 'json');

        return $this->handleView(
            $this->view([
                'currencies' => $currencies,
                'markets' => $markets,
            ], Response::HTTP_OK)
        );
    }
}
