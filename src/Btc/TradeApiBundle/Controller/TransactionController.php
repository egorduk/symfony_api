<?php

namespace Btc\TradeApiBundle\Controller;

use Btc\FrontendApiBundle\Exception\Rest\NotFoundException;
use Btc\TradeApiBundle\Presenter\Transactions;
use FOS\RestBundle\Controller\Annotations;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Request\ParamFetcherInterface;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Symfony\Component\HttpFoundation\Response;

class TransactionController extends FOSRestController
{
    /**
     * Gets transactions.
     *
     * ### Request URL example ###
     * GET /api/trade/v1/transactions?market=btc-usd&scope=hour
     * ### Success response example ###
     *     {
     *       "transactions": [
     *          {
     *              "id": 3,
     *              "amount": "1.00000000",
     *              "price": "7901.89575000",
     *              "side": "SELL",
     *              "timestamp": 1522143522
     *          }
     *      ]
     *     }
     * ### Error response example ###
     *     {
     *       "status": 400,
     *       "error": "INCORRECT_DATA"
     *     },
     *     {
     *       "status": 404,
     *       "error": "NOT_FOUND"
     *     }
     *
     * @ApiDoc(
     *   resource = true,
     *   description = "Gets transactions",
     *   statusCodes = {
     *     Response::HTTP_OK = "Returned when successful",
     *     Response::HTTP_BAD_REQUEST = "Returned when inputted incorrect data",
     *     Response::HTTP_NOT_FOUND = "Returned when the data are not found"
     *   },
     *   section = "Trade"
     * )
     *
     * @Annotations\Get("/transactions")
     *
     * @Annotations\QueryParam(name="market", requirements="\S+", description="Market code name.")
     * @Annotations\QueryParam(name="scope", requirements="(hour|minute)", default="hour", strict=true, description="Timespan for transactions.")
     *
     * @param ParamFetcherInterface $paramFetcher
     *
     * @return Response
     */
    public function getTransactionsAction(ParamFetcherInterface $paramFetcher)
    {
        $market = $paramFetcher->get('market');
        $scope = $paramFetcher->get('scope');

        if (!$market = $this->get('rest.service.market')->getOneBy(['slug' => $market])) {
            throw new NotFoundException();
        }

        $transactions = $this->get('rest.repository.transaction')->findAllWithinScopeInMarket($scope, $market);

        $data = new Transactions($transactions);

        return $this->handleView(
            $this->view($data->presentAsJson(), Response::HTTP_OK)
        );
    }
}
