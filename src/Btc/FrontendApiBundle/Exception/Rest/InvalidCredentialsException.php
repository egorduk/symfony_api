<?php

namespace Btc\FrontendApiBundle\Exception\Rest;

use Btc\FrontendApiBundle\Classes\RestCodeError;
use Btc\FrontendApiBundle\Exception\RestException;
use Symfony\Component\HttpFoundation\Response;

class InvalidCredentialsException extends RestException
{
    public function __construct()
    {
        parent::__construct(RestCodeError::INVALID_USERNAME_OR_PASSWORD, Response::HTTP_BAD_REQUEST);
    }
}
