<?php

namespace Btc\FrontendApiBundle\Exception\Rest;

use Btc\FrontendApiBundle\Classes\RestCodeError;
use Btc\FrontendApiBundle\Exception\RestException;
use Symfony\Component\HttpFoundation\Response;

class UserBlockedException extends RestException
{
    public function __construct()
    {
        parent::__construct(RestCodeError::USER_BLOCKED, Response::HTTP_BAD_REQUEST);
    }
}
