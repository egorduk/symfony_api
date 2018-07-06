<?php

namespace Btc\FrontendApiBundle\Controller;

use Btc\FrontendApiBundle\Exception\Rest\NotFoundException;
use FOS\RestBundle\Controller\Annotations;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Request\ParamFetcherInterface;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Symfony\Component\HttpFoundation\Response;

class NewsController extends FOSRestController
{
    /**
     * Gets all news records.
     *
     * ### Request URL example ###
     * GET /api/v1/news?offset=0&limit=10
     * ### Success response example ###
     *     {
     *       "news": [{
     *         "id": 2,
     *         "slug": "btc-usd",
     *         "title": "title",
     *         "content": "content",
     *         "created_at": "2018-01-15T00:00:00+00:00",
     *         "published_at": "2018-01-24T00:00:00+00:00"
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
     *   description = "Gets all news records",
     *   output = "Btc\CoreBundle\Entity\Article",
     *   statusCodes = {
     *     Response::HTTP_OK = "Returned when successful",
     *     Response::HTTP_NOT_FOUND = "Returned when the data are not found"
     *   },
     *   section = "News"
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
    public function getNewsAction(ParamFetcherInterface $paramFetcher)
    {
        $offset = $paramFetcher->get('offset');
        $offset = null === $offset ? 0 : $offset;
        $limit = $paramFetcher->get('limit');

        if ($news = $this->get('rest.service.news')->findAllPublished($limit, $offset)) {
            return $this->handleView(
                $this->view([
                    'news' => $news,
                ], Response::HTTP_OK)
            );
        }

        throw new NotFoundException();
    }

    /**
     * Gets news by slug.
     *
     * ### Request URL example ###
     * GET /api/v1/news/slug/test
     * ### Success response example ###
     *     {
     *       "news": [{
     *         "id": 2,
     *         "slug": "test",
     *         "title": "title",
     *         "content": "content",
     *         "created_at": "2018-01-15T00:00:00+00:00",
     *         "published_at": "2018-01-24T00:00:00+00:00"
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
     *   description = "Gets news by slug",
     *   output = "Btc\CoreBundle\Entity\Article",
     *   statusCodes = {
     *     Response::HTTP_OK = "Returned when successful",
     *     Response::HTTP_NOT_FOUND = "Returned when the data are not found"
     *   },
     *   section = "News"
     * )
     *
     * @Annotations\Get("/news/slug/{newsSlug}", requirements = { "newsSlug" = "\S+" })
     *
     * @param string $newsSlug
     *
     * @return Response
     *
     * @throws NotFoundException when item do not exist
     */
    public function getNewsBySlugAction($newsSlug)
    {
        if ($news = $this->get('rest.service.news')->findOneBySlug($newsSlug)) {
            return $this->handleView(
                $this->view([
                    'news' => $news,
                ], Response::HTTP_OK)
            );
        }

        throw new NotFoundException();
    }
}
