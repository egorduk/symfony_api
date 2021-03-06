<?php

namespace Btc\FrontendApiBundle\Exception\Rest;

use Btc\FrontendApiBundle\Classes\RestCodeError;
use Btc\FrontendApiBundle\Exception\RestException;
use Symfony\Component\HttpFoundation\Response;

class OrderInvalidPriceException extends RestException
{
    public function __construct()
    {
        parent::__construct(RestCodeError::ORDER_INVALID_PRICE, Response::HTTP_BAD_REQUEST);
    }
}
