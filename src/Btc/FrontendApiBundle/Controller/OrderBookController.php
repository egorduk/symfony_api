<?php

namespace Btc\FrontendApiBundle\Controller;

use Btc\FrontendApiBundle\Exception\Rest\NotFoundException;
use FOS\RestBundle\Controller\Annotations;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Request\ParamFetcherInterface;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Symfony\Component\HttpFoundation\Response;

class OrderBookController extends FOSRestController
{
    /**
     * Gets order books by market slug.
     *
     * ### Request URL example ###
     * GET /api/v1/orderbooks/markets/slug/ltc-btc?offset=0&limit=100
     * ### Success response example ###
     *     {
     *       "orderbooks": [{
     *         "bids": [{
     *           "id": 0,
     *           "platform": "BFX",
     *           "price": 0.01699,
     *           "amount": 2.03
     *         }],
     *         "asks": [{
     *           "id": 0,
     *           "platform": "BFX",
     *           "price": 0.01699,
     *           "amount": 2.03
     *         }]
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
     *   description = "Gets order books by market slug",
     *   statusCodes = {
     *     Response::HTTP_OK = "Returned when successful",
     *     Response::HTTP_NOT_FOUND = "Returned when the data are not found"
     *   },
     *   section = "Order"
     * )
     *
     * @Annotations\QueryParam(name="offset", requirements="\d+", nullable=true, default="0", description="Offset from which to start listing items.")
     * @Annotations\QueryParam(name="limit", requirements="\d+", default="100", description="How many items to return.")
     *
     * @Annotations\Get("/orderbooks/markets/slug/{marketSlug}", requirements = { "marketSlug" = "\S+" })
     *
     * @param string                $marketSlug   Market slug (usd-btc, btc-eur, eth-eur and etc.)
     * @param ParamFetcherInterface $paramFetcher param fetcher service
     *
     * @return Response
     *
     * @throws NotFoundException when items do not exist
     */
    public function getOrderBooksByMarketSlugAction(ParamFetcherInterface $paramFetcher, $marketSlug)
    {
        $offset = $paramFetcher->get('offset');
        $offset = null === $offset ? 0 : $offset;
        $limit = $paramFetcher->get('limit');

        $orderBookService = $this->get('rest.service.order_book');

        $bids = $orderBookService->getBuyDeals($marketSlug, $limit, $offset);
        $asks = $orderBookService->getSellDeals($marketSlug, $limit, $offset);

        if (empty($bids) && empty($asks)) {
            throw new NotFoundException();
        }

        return $this->handleView(
            $this->view([
                'orderbooks' => [
                    'bids' => $bids,
                    'asks' => $asks,
                ],
            ], Response::HTTP_OK)
        );
    }
}
