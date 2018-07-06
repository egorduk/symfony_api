<?php

namespace Btc\FrontendApiBundle\Controller;

use Btc\FrontendApiBundle\Exception\Rest\NotFoundException;
use FOS\RestBundle\Controller\Annotations;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Request\ParamFetcherInterface;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Symfony\Component\HttpFoundation\Response;

class BankController extends FOSRestController
{
    /**
     * Gets all banks.
     *
     * ### Request URL example ###
     * GET /api/v1/banks?offset=0&limit=10
     * ### Success response example ###
     *     {
     *       "banks": [{
     *          "id": 1,
     *          "name": "EgoPay",
     *          "slug": "egopay",
     *          "fiat": true,
     *          "payment_method": "e-currency",
     *          "deposit_available": true,
     *          "withdrawal_available": true
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
     *   description = "Gets all banks",
     *   output = "Btc\CoreBundle\Entity\Bank",
     *   statusCodes = {
     *     Response::HTTP_OK = "Returned when successful",
     *     Response::HTTP_NOT_FOUND = "Returned when items do not exist"
     *   },
     *   section = "Bank"
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
    public function getBanksAction(ParamFetcherInterface $paramFetcher)
    {
        $offset = $paramFetcher->get('offset');
        $offset = null === $offset ? 0 : $offset;
        $limit = $paramFetcher->get('limit');

        if ($data = $this->get('rest.service.bank')->all($limit, $offset)) {
            return $this->handleView(
                $this->view([
                    'banks' => $data,
                ], Response::HTTP_OK)
            );
        }

        throw new NotFoundException();
    }
}
