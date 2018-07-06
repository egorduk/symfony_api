<?php

namespace Btc\FrontendApiBundle\Controller;

use Btc\FrontendApiBundle\Exception\Rest\NotFoundException;
use FOS\RestBundle\Controller\Annotations;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Request\ParamFetcherInterface;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Symfony\Component\HttpFoundation\Response;

class CurrencyController extends FOSRestController
{
    /**
     * Gets all currencies.
     *
     * ### Request URL example ###
     * GET /api/v1/currencies?offset=0&limit=10
     * ### Success response example ###
     *     {
     *       "currencies": [{
     *           "id": 1,
     *           "code": "USD",
     *           "sign": "$",
     *           "format": 2,
     *           "crypto": false,
     *           "eth": false,
     *           "is_erc_token": false,
     *           "contract_address": "",
     *           "contract_abi": ""
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
     *   description = "Gets all currencies",
     *   output = "Btc\CoreBundle\Entity\Currency",
     *   statusCodes = {
     *     Response::HTTP_OK = "Returned when successful",
     *     Response::HTTP_NOT_FOUND = "Returned when the data are not found"
     *   },
     *   section = "Currency"
     * )
     *
     * @Annotations\QueryParam(name="offset", requirements="\d+", nullable=true, default="0", description="Offset from which to start listing items.")
     * @Annotations\QueryParam(name="limit", requirements="\d+", default="10", description="How many items to return.")
     *
     * @param ParamFetcherInterface $paramFetcher param fetcher service
     *
     * @return Response
     *
     * @throws NotFoundException when items do not exist
     */
    public function getCurrenciesAction(ParamFetcherInterface $paramFetcher)
    {
        $offset = $paramFetcher->get('offset');
        $offset = null === $offset ? 0 : $offset;
        $limit = $paramFetcher->get('limit');

        if ($data = $this->get('rest.service.currency')->all($limit, $offset)) {
            return $this->handleView(
                $this->view([
                    'currencies' => $data,
                ], Response::HTTP_OK)
            );
        }

        throw new NotFoundException();
    }
}
