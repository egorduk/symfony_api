<?php

namespace Btc\FrontendApiBundle\Exception\Rest;

use Btc\FrontendApiBundle\Classes\RestCodeError;
use Btc\FrontendApiBundle\Exception\RestException;
use Symfony\Component\HttpFoundation\Response;

class TooOftenAddressRequestException extends RestException
{
    public function __construct()
    {
        parent::__construct(RestCodeError::TOO_OFTEN_ADDRESS_REQUEST, Response::HTTP_BAD_REQUEST);
    }
}
