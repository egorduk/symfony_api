<?php

namespace Btc\FrontendApiBundle\Exception\Rest;

use Btc\FrontendApiBundle\Classes\RestCodeError;
use Btc\FrontendApiBundle\Exception\RestException;
use Symfony\Component\HttpFoundation\Response;

class UserNotFoundException extends RestException
{
    public function __construct()
    {
        parent::__construct(RestCodeError::USER_NOT_FOUND, Response::HTTP_NOT_FOUND);
    }
}
