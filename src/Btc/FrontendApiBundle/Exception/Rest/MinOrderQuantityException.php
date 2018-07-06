<?php

namespace Btc\FrontendApiBundle\Exception\Rest;

use Btc\FrontendApiBundle\Classes\RestCodeError;
use Btc\FrontendApiBundle\Exception\RestException;
use Symfony\Component\HttpFoundation\Response;

class MinOrderQuantityException extends RestException
{
    public function __construct()
    {
        parent::__construct(RestCodeError::MARKET_MIN_ORDER_QUANTITY_NOT_REACHED, Response::HTTP_BAD_REQUEST);
    }
}
