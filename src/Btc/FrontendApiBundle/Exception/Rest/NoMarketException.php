<?php

namespace Btc\FrontendApiBundle\Exception\Rest;

use Btc\FrontendApiBundle\Classes\RestCodeError;
use Btc\FrontendApiBundle\Exception\RestException;
use Symfony\Component\HttpFoundation\Response;

class NoMarketException extends RestException
{
    public function __construct()
    {
        parent::__construct(RestCodeError::NO_MARKET, Response::HTTP_NOT_FOUND);
    }
}
