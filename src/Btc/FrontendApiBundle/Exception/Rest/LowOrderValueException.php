<?php

namespace Btc\FrontendApiBundle\Exception\Rest;

use Btc\FrontendApiBundle\Classes\RestCodeError;
use Btc\FrontendApiBundle\Exception\RestException;
use Symfony\Component\HttpFoundation\Response;

class LowOrderValueException extends RestException
{
    public function __construct()
    {
        parent::__construct(RestCodeError::ORDER_VALUE_TOO_LOW, Response::HTTP_NOT_FOUND);
    }
}
