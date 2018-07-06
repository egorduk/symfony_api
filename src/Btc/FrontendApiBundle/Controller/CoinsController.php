<?php

namespace Btc\FrontendApiBundle\Controller;


use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use FOS\RestBundle\Controller\Annotations;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Request\ParamFetcherInterface;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Btc\FrontendApiBundle\Exception\Rest\InvalidFormException;

class CoinsController extends FOSRestController
{
    /**
     * Submit your own coin.
     *
     * @ApiDoc(
     *   resource = true,
     *   description = "Submit your own coin",
     *   statusCodes = {
     *     Response::HTTP_OK = "Returned when successful",
     *     Response::HTTP_NOT_FOUND = "Returned when the data are not found"
     *   },
     *   section = "Coins"
     * )
     *
     * @Annotations\Post("/coins/submit")
     *
     *
     * @Annotations\RequestParam(name="blockchain")
     * @Annotations\RequestParam(name="icoTokenPrice")
     * @Annotations\RequestParam(name="isListingToken")
     * @Annotations\RequestParam(name="projectLink")
     * @Annotations\RequestParam(name="representativeEmail")
     * @Annotations\RequestParam(name="representativeName")
     * @Annotations\RequestParam(name="representativePosition")
     * @Annotations\RequestParam(name="saleEnd")
     * @Annotations\RequestParam(name="saleEndTime")
     * @Annotations\RequestParam(name="saleStart")
     * @Annotations\RequestParam(name="saleStartTime")
     * @Annotations\RequestParam(name="socialThreads")
     * @Annotations\RequestParam(name="tokenName")
     * @Annotations\RequestParam(name="tokenSupply")
     * @Annotations\RequestParam(name="tokenTicker")
     *
     * @param Request $request
     *
     * @return Response
     *
     */
    public function postSubmitAction(Request $request)
    {
        $coinSubmissionService = $this->get('rest.service.coin_submit');
        $submission = $coinSubmissionService->processForm($request);

        if (!empty($submission)) {
            $id = $submission->getId();
            if (empty($id)) {
                throw new InvalidFormException();
            }
        } else {
            throw new InvalidFormException();
        }


        return $this->handleView(
            $this->view([
                'submission' => $submission,
            ], Response::HTTP_OK)
        );

    }
}
