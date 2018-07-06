<?php

namespace Btc\FrontendApiBundle\Exception\Rest;

use Btc\FrontendApiBundle\Classes\RestCodeError;
use Btc\FrontendApiBundle\Exception\RestException;
use Symfony\Component\HttpFoundation\Response;

class UnknownErrorException extends RestException
{
    public function __construct()
    {
        parent::__construct(RestCodeError::UNKNOWN_ERROR, Response::HTTP_INTERNAL_SERVER_ERROR);
    }
}
