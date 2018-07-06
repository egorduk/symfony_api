<?php

namespace Btc\TradeApiBundle\Controller;

use FOS\RestBundle\Controller\FOSRestController;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Symfony\Component\HttpFoundation\Response;

class VersionController extends FOSRestController
{
    /**
     * Gets version api.
     *
     * ### Request URL example ###
     * GET /api/trade/v1/version
     * ### Success response example ###
     *     {
     *       "version": ""
     *     }
     *
     * @ApiDoc(
     *   resource = true,
     *   description = "Gets version api",
     *   statusCodes = {
     *     Response::HTTP_OK = "Returned when successful"
     *   },
     *   section = "Trade"
     * )
     *
     * @return Response
     */
    public function getVersionAction()
    {
        return $this->handleView(
            $this->view([
                'version' => $this->container->getParameter('rest_api_version'),
            ], Response::HTTP_OK)
        );
    }
}
