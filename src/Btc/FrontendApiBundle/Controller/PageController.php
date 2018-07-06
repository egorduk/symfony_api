<?php

namespace Btc\FrontendApiBundle\Controller;

use Btc\CoreBundle\Entity\Page;
use Btc\FrontendApiBundle\Exception\Rest\NotFoundException;
use Btc\FrontendApiBundle\Exception\RestException;
use FOS\RestBundle\Controller\Annotations;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Request\ParamFetcherInterface;
use JMS\Serializer\SerializationContext;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Symfony\Component\HttpFoundation\Response;

class PageController extends FOSRestController
{
    /**
     * Gets static page by path.
     *
     * @ApiDoc(
     *   resource = true,
     *   description = "Get static page by path",
     *   output = "Btc\CoreBundle\Entity\Page",
     *   statusCodes = {
     *     Response::HTTP_OK = "Returned when successful",
     *     Response::HTTP_NOT_FOUND = "Returned when the data are not found"
     *   },
     *   section = "Page"
     * )
     *
     * @Annotations\QueryParam(name="path", requirements="[\S]+", description="Path of the page")
     *
     * @param ParamFetcherInterface $paramFetcher param fetcher service
     *
     * @return Response
     *
     * @throws RestException when items do not exist
     */
    public function getPageAction(ParamFetcherInterface $paramFetcher)
    {
        $path = $paramFetcher->get('path');

        if ($page = $this->get('rest.repository.page')->findPage($path)) {
            $serializer = $this->get('jms_serializer');

            $apiGroupData = $serializer->serialize($page, 'json', SerializationContext::create()->setGroups(['api']));
            $page = $serializer->deserialize($apiGroupData, Page::class, 'json');

            return $this->handleView(
                $this->view([
                    'page' => $page,
                ], Response::HTTP_OK)
            );
        }

        throw new NotFoundException();
    }
}
