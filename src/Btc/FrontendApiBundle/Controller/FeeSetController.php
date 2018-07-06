<?php

namespace Btc\FrontendApiBundle\Controller;

use Btc\FrontendApiBundle\Exception\Rest\NotFoundException;
use FOS\RestBundle\Controller\Annotations;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Request\ParamFetcherInterface;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Symfony\Component\HttpFoundation\Response;

class FeeSetController extends FOSRestController
{
    /**
     * Gets fees by market id.
     *
     * ### Request URL example ###
     * GET /api/v1/fees/markets/id/1?offset=0&limit=2
     * ### Success response example ###
     *     {
     *      "fees": {
     *         "buy": {
     *            "fixed": "0.00000000",
     *            "percent": "0.15000000"
     *         },
     *         "sell": {
     *            "fixed": "0.00000000",
     *            "percent": "0.15000000"
     *         }
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
     *   description = "Gets fees by market id",
     *   statusCodes = {
     *     Response::HTTP_OK = "Returned when successful",
     *     Response::HTTP_BAD_REQUEST = "Returned when inputted incorrect data",
     *     Response::HTTP_NOT_FOUND = "Returned when the data are not found"
     *   },
     *   section = "Fee",
     *   authentication = true
     * )
     *
     * @Annotations\QueryParam(name="offset", requirements="\d+", nullable=true, default="0", description="Offset from which to start listing items.")
     * @Annotations\QueryParam(name="limit", requirements="\d+", default="2", description="How many items to return.")
     *
     * @Annotations\Get("/fees/markets/id/{marketId}", requirements = { "marketId" = "\d+" })
     *
     * @param ParamFetcherInterface $paramFetcher param fetcher service
     * @param int                   $marketId     Market id
     *
     * @return Response
     *
     * @throws NotFoundException when items do not exist
     */
    public function getFeesAction(ParamFetcherInterface $paramFetcher, $marketId)
    {
        if (!$market = $this->get('rest.service.market')->get($marketId)) {
            throw new NotFoundException();
        }

        $offset = $paramFetcher->get('offset');
        $offset = null === $offset ? 0 : $offset;
        $limit = $paramFetcher->get('limit');

        $feeSet = $this->get('rest.service.fee_service')
            ->getFeeSet($this->getUser(), $market, $limit, $offset);

        if (!empty($feeSet->getFees())) {
            list($fixedBuy, $percentBuy) = $feeSet->getBuyFeeByMarket($marketId);
            list($fixedSell, $percentSell) = $feeSet->getSellFeeByMarket($marketId);

            return $this->handleView(
                $this->view([
                    'fees' => [
                        'buy' => [
                            'fixed' => $fixedBuy,
                            'percent' => $percentBuy,
                        ],
                        'sell' => [
                            'fixed' => $fixedSell,
                            'percent' => $percentSell,
                        ],
                    ],
                ], Response::HTTP_OK)
            );
        }

        throw new NotFoundException();
    }
}
