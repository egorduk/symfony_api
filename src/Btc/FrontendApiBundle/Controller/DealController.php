<?php

namespace Btc\FrontendApiBundle\Controller;

use Btc\CoreBundle\Entity\Market;
use Btc\FrontendApiBundle\Exception\Rest\NotFoundException;
use FOS\RestBundle\Controller\Annotations;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Request\ParamFetcherInterface;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Symfony\Component\HttpFoundation\Response;

class DealController extends FOSRestController
{
    const LATEST_TRANSACTION_CNT = 50;

    /**
     * Gets deals by market id.
     *
     * ### Request URL example ###
     * GET /api/v1/deals/markets/id/1
     * ### Success response example ###
     *     {
     *       "deals": {
     *         "id": "19",
     *         "amount": "0.08632515",
     *         "price": "13974.97500000",
     *         "time": "1515756287"
     *       }
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
     *   description = "Gets deals by market id",
     *   output = {
     *     "class" = "Btc\CoreBundle\Entity\Transaction",
     *     "groups" = {"api_get_deals_by_market_id"},
     *   },
     *   statusCodes = {
     *     Response::HTTP_OK = "Returned when successful",
     *     Response::HTTP_BAD_REQUEST = "Returned when inputted incorrect data",
     *     Response::HTTP_NOT_FOUND = "Returned when the data are not found"
     *   },
     *   section = "Deal"
     * )
     *
     * @Annotations\Get("/deals/markets/id/{marketId}", requirements = { "marketId" = "\d+" })
     *
     * @param int $marketId Market id
     *
     * @return Response
     *
     * @throws NotFoundException when items do not exist
     */
    public function getDealsByMarketIdAction($marketId)
    {
        $market = $this->get('rest.service.market')->get($marketId);

        if (!$market instanceof Market) {
            throw new NotFoundException();
        }

        $deals = $this->get('rest.repository.transaction')->getLatestTransactionsByMarket($market, self::LATEST_TRANSACTION_CNT);

        return $this->handleView(
            $this->view([
                'deals' => $deals,
            ], Response::HTTP_OK)
        );
    }

    /**
     * Gets latest market prices.
     *
     * ### Request URL example ###
     * GET /api/v1/deals/markets?offset=0&limit=10
     * ### Success response example ###
     *     {
     *       "data": {
     *         "market": "btc-usd",
     *         "deals": {
     *           "id": "2",
     *           "amount": "1.00000000",
     *           "price": "10.00000000",
     *           "time": "1510643640"
     *         }
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
     *   description = "Gets latest market deals",
     *   output = {
     *     "class" = "Btc\CoreBundle\Entity\Market",
     *     "groups" = {"api_get_deals_markets"},
     *   },
     *   statusCodes = {
     *     Response::HTTP_OK = "Returned when successful",
     *     Response::HTTP_NOT_FOUND = "Returned when the data are not found"
     *   },
     *   section = "Deal",
     * )
     *
     * @Annotations\Get("/deals/markets")
     *
     * @Annotations\QueryParam(name="offset", requirements="\d+", nullable=true, default="0", description="Offset from which to start listing items.")
     * @Annotations\QueryParam(name="limit", requirements="\d+", nullable=true, default="10", description="How many items to return.")
     *
     * @param ParamFetcherInterface $paramFetcher param fetcher service
     *
     * @return Response
     *
     * @throws NotFoundException when items do not exist
     */
    public function getDealsAction(ParamFetcherInterface $paramFetcher)
    {
        $offset = $paramFetcher->get('offset');
        $offset = null === $offset ? 0 : $offset;
        $limit = $paramFetcher->get('limit');

        if (empty($limit)) {
            $limit = null;
        }

        $markets = $this->get('rest.service.market')->all($limit, $offset);

        if (!$markets) {
            throw new NotFoundException();
        }

        $transactionRepository = $this->get('rest.repository.transaction');

        $data = [];

        foreach ($markets as $market) {
            $data[] = [
                'market' => $market->getSlug(),
                'deals' => $transactionRepository->getLatestTransactionsByMarket($market, self::LATEST_TRANSACTION_CNT),
            ];
        }

        return $this->handleView(
            $this->view([
                'data' => $data,
            ], Response::HTTP_OK)
        );
    }
}
