<?php

namespace Btc\FrontendApiBundle\Controller;

use Btc\CoreBundle\Entity\Market;
use Btc\FrontendApiBundle\Exception\Rest\NoMarketException;
use Btc\FrontendApiBundle\Exception\Rest\UnknownOhlcvIntervalException;
use Btc\CoreBundle\Entity\OhlcvCandle;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use FOS\RestBundle\Controller\Annotations;
use FOS\RestBundle\Controller\FOSRestController;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Symfony\Component\HttpFoundation\Response;
use JMS\Serializer\SerializationContext;

class ExportController extends FOSRestController
{
    const LIMIT = 120;

    /**
     * Gets market candles data.
     *
     * ### Request URL example ###
     * GET /api/v1/export/candles/1/1d
     * ### Success response example ###
     *     {
     *       {
     *          "timestamp": 172800,
     *          "open": 1,
     *          "high": 2,
     *          "low": 2,
     *          "close": 2,
     *          "volume": 5
     *       }
     *     }
     * ### Error response example ###
     *      {
     *       "status": 400,
     *       "error": "INCORRECT_DATA"
     *     }
     *     {
     *       "status": 404,
     *       "error": "UNKNOWN_OHLCV_INTERVAL | NO_MARKET"
     *     }
     *
     * @ApiDoc(
     *   resource = true,
     *   description = "Gets market candles data",
     *   output = {
     *     "class" = "Btc\CoreBundle\Entity\OhlcvCandle",
     *     "groups" = {"api_get_export_candles"},
     *   },
     *   statusCodes = {
     *     Response::HTTP_OK = "Returned when successful",
     *     Response::HTTP_BAD_REQUEST = "Returned when inputted incorrect data",
     *     Response::HTTP_NOT_FOUND = "Returned when the data are not found"
     *   },
     *   section = "Candle"
     * )
     *
     * @Annotations\Get("/export/candles/{marketId}/{interval}", requirements = { "marketId" = "\d+", "interval" = "\d+[mhdw]" })
     *
     * @param int    $marketId
     * @param string $interval
     *
     * @return Response
     *
     * @throws UnknownOhlcvIntervalException when items do not exist
     * @throws NoMarketException             when items do not exist
     */
    public function getCandlesAction($marketId, $interval)
    {
        $market = $this->get('rest.service.market')->get($marketId);

        if (!$market instanceof Market) {
            throw new NoMarketException();
        }

        $em = $this->get('em');
        $cm = $em->getClassMetadata(OhlcvCandle::class);

        $table = OhlcvCandle::getTableNameForInterval($interval);

        if (empty($table)) {
            throw new UnknownOhlcvIntervalException();
        }

        /* @var ClassMetadataInfo $cm */
        $cm->setPrimaryTable(['name' => $table]);

        $qb = $em->createQueryBuilder()
            ->select('f')
            ->from(OhlcvCandle::class, 'f')
            ->where('f.marketId = :marketId')
            ->setParameters(['marketId' => $marketId])
            ->setMaxResults(self::LIMIT)
            ->orderBy('f.intervalId', 'desc');

        $candles = $qb->getQuery()->getResult();

        if (!empty($candles)) {
            $serializer = $this->get('jms_serializer');

            foreach ($candles as $c) {
                $c->setIntervalName($interval);
            }

            $candles = $serializer->serialize($candles, 'json', SerializationContext::create()->setGroups(['api']));
            $candles = json_decode($candles, true);
        }

        $view = $this->view($candles, Response::HTTP_OK);

        $format = $this->get('request')->getRequestFormat();

        if ($format === 'csv') {
            $buf = '';

            if (!empty($candles)) {
                $buf = implode(',', array_keys($candles[0]))."\r\n";

                foreach ($candles as $candle) {
                    $buf .= implode(',', $candle)."\r\n";
                }
            }

            return new Response(
                $buf,
                Response::HTTP_OK,
                array(
                    'Content-Type' => 'application/csv',
                    'Content-Disposition' => 'filename="ohlcv_data-'.$market->getSlug().'-'.$interval.'.csv"',
                )
            );
        }

        return $this->handleView($view);
    }
}
