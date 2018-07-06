<?php

namespace Btc\TradeApiBundle\Controller;

use Btc\FrontendApiBundle\Exception\Rest\NotFoundException;
use Btc\TradeApiBundle\Model\Volume;
use Btc\TradeApiBundle\Presenter\AccountBalance;
use Btc\TradeApiBundle\Presenter\AccountTransactions;
use Btc\TradeApiBundle\Presenter\VolumeFeePresenter;
use FOS\RestBundle\Controller\Annotations;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Request\ParamFetcherInterface;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class AccountController extends FOSRestController
{
    /**
     * Gets user wallet balances.
     *
     * ### Request URL example ###
     * GET /api/trade/v1/account/balance?market=btc-usd
     * ### Success response example ###
     *     {
     *       "wallets": [
     *          {
     *              "currency": "",
     *              "total": "",
     *              "reserved": "",
     *              "available": ""
     *          }
     *       ]
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
     *   description = "Gets user wallet balances",
     *   statusCodes = {
     *     Response::HTTP_OK = "Returned when successful",
     *     Response::HTTP_BAD_REQUEST = "Returned when inputted incorrect data",
     *     Response::HTTP_NOT_FOUND = "Returned when the data are not found"
     *   },
     *   section = "Trade",
     *   authentication = true
     * )
     *
     * @Annotations\Get("/account/balance")
     *
     * @Annotations\QueryParam(name="market", requirements="\S+", description="Market code name.")
     * @Annotations\QueryParam(name="nonce", requirements="\S+", description="Nonce.")
     *
     * @param ParamFetcherInterface $paramFetcher
     *
     * @return Response
     *
     * @throws NotFoundHttpException when data do not exist
     */
    public function getAccountBalanceAction(ParamFetcherInterface $paramFetcher)
    {
        if (!$market = $this->get('rest.service.market')->getOneBy(['slug' => $paramFetcher->get('market')])) {
            throw new NotFoundException();
        }

        $user = $this->getUser();

        $wallets = $this->get('rest.repository.wallet')->findAllUserWalletsWithinMarket($user, $market);

        $data = new AccountBalance($wallets);

        return $this->handleView(
            $this->view($data->presentAsJson(), Response::HTTP_OK)
        );
    }

    /**
     * Gets user volume and current fee set.
     *
     * ### Request URL example ###
     * GET /api/trade/v1/account/volume?market=btc-usd
     * ### Success response example ###
     *     {
     *          "currency_volume": "",
     *          "volume": "",
     *          "fee_plan": "",
     *          "fee_percent": ""
     *     }
     * ### Error response example ###
     *     {
     *       "status": 404,
     *       "error": "NOT_FOUND"
     *     },
     *     {
     *       "status": 404,
     *       "error": "NOT_FOUND"
     *     }
     *
     * @ApiDoc(
     *   resource = true,
     *   description = "Gets user volume and current fee set",
     *   statusCodes = {
     *     Response::HTTP_OK = "Returned when successful",
     *     Response::HTTP_BAD_REQUEST = "Returned when inputted incorrect data",
     *     Response::HTTP_NOT_FOUND = "Returned when the data are not found"
     *   },
     *   section = "Trade",
     *   authentication = true
     * )
     *
     * @Annotations\Get("/account/volume")
     *
     * @Annotations\QueryParam(name="market", requirements="\S+", description="Market code name.")
     * @Annotations\QueryParam(name="nonce", requirements="\S+", description="Nonce.")
     *
     * @param ParamFetcherInterface $paramFetcher
     *
     * @return Response
     *
     * @throws NotFoundHttpException when data do not exist
     */
    public function getAccountVolumeAction(ParamFetcherInterface $paramFetcher)
    {
        if (!$market = $this->get('rest.service.market')->getOneBy(['slug' => $paramFetcher->get('market')])) {
            throw new NotFoundException();
        }

        $user = $this->getUser();

        $feeSets = $this->get('rest.service.fee_service')->getFeeSet($user, $market);

        $data = new VolumeFeePresenter(new Volume(), $feeSets);

        return $this->handleView(
            $this->view($data->presentAsJson(), Response::HTTP_OK)
        );
    }

    /**
     * Gets user transactions.
     *
     * ### Request URL example ###
     * GET /api/trade/v1/account/transactions?market=btc-usd
     * ### Success response example ###
     *     {
     *          "transactions": [
     *              {
     *                  "id": 0,
     *                  "order_id": 0,
     *                  "status": "",
     *                  "amount": "",
     *                  "price": "",
     *                  "fee": "",
     *                  "timestamp": 0
     *              }
     *          ]
     *     }
     * ### Error response example ###
     *     {
     *       "status": 404,
     *       "error": "NOT_FOUND"
     *     },
     *     {
     *       "status": 404,
     *       "error": "NOT_FOUND"
     *     }
     *
     * @ApiDoc(
     *   resource = true,
     *   description = "Gets user transactions",
     *   statusCodes = {
     *     Response::HTTP_OK = "Returned when successful",
     *     Response::HTTP_BAD_REQUEST = "Returned when inputted incorrect data",
     *     Response::HTTP_NOT_FOUND = "Returned when the data are not found"
     *   },
     *   section = "Trade",
     *   authentication = true
     * )
     *
     * @Annotations\Get("/account/transactions")
     *
     * @Annotations\QueryParam(name="market", requirements="\S+", description="Market code name.")
     * @Annotations\QueryParam(name="offset", requirements="\d+", strict=true, default="0", description="Grab transactions from specific offset.")
     * @Annotations\QueryParam(name="sort", requirements="(asc|desc)", strict=true, default="desc", description="Sort order.")
     * @Annotations\QueryParam(name="nonce", requirements="\S+", description="Nonce.")
     *
     * @param ParamFetcherInterface $paramFetcher
     *
     * @return Response
     *
     * @throws NotFoundHttpException when data do not exist
     */
    public function getAccountTransactionsAction(ParamFetcherInterface $paramFetcher)
    {
        if (!$market = $this->get('rest.service.market')->getOneBy(['slug' => $paramFetcher->get('market')])) {
            throw new NotFoundException();
        }

        $user = $this->getUser();

        $transactions = $this->get('rest.repository.transaction')->getUserTransactionsByMarket($user, $market);

        $data = new AccountTransactions($transactions);

        return $this->handleView(
            $this->view($data->presentAsJson(), Response::HTTP_OK)
        );
    }
}
