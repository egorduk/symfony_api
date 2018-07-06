<?php

namespace Btc\FrontendApiBundle\Controller;

use Btc\FrontendApiBundle\Exception\Rest\NotFoundException;
use FOS\RestBundle\Controller\Annotations;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Request\ParamFetcherInterface;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Symfony\Component\HttpFoundation\Response;

class CountryController extends FOSRestController
{
    /**
     * Gets all countries.
     *
     * ### Request URL example ###
     * GET /api/v1/countries?offset=0&limit=10
     * ### Success response example ###
     *     {
     *       "countries": [{
     *           "id": 1,
     *           "name": "AFGHANISTAN",
     *           "iso2": "AF",
     *           "iso3": "AFG",
     *           "hidden": true,
     *           "restricted": false
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
     *   description = "Gets all countries",
     *   output = "Btc\CoreBundle\Entity\Country",
     *   statusCodes = {
     *     Response::HTTP_OK = "Returned when successful",
     *     Response::HTTP_NOT_FOUND = "Returned when the data are not found"
     *   },
     *   section = "Country"
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
    public function getCountriesAction(ParamFetcherInterface $paramFetcher)
    {
        $offset = $paramFetcher->get('offset');
        $offset = null === $offset ? 0 : $offset;
        $limit = $paramFetcher->get('limit');
        $limit = $limit ? $limit : null;

        if ($data = $this->get('rest.service.country')->all($limit, $offset)) {
            return $this->handleView(
                $this->view([
                    'countries' => $data,
                ], Response::HTTP_OK)
            );
        }

        throw new NotFoundException();
    }
}
