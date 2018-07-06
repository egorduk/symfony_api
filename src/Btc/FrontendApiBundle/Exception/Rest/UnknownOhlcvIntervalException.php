<?php

namespace Btc\FrontendApiBundle\Exception\Rest;

use Btc\FrontendApiBundle\Classes\RestCodeError;
use Btc\FrontendApiBundle\Exception\RestException;
use Symfony\Component\HttpFoundation\Response;

class UnknownOhlcvIntervalException extends RestException
{
    public function __construct()
    {
        parent::__construct(RestCodeError::UNKNOWN_OHLCV_INTERVAL, Response::HTTP_NOT_FOUND);
    }
}
