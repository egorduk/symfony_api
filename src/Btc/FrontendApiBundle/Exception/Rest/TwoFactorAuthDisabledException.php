<?php

namespace Btc\FrontendApiBundle\Exception\Rest;

use Btc\FrontendApiBundle\Classes\RestCodeError;
use Btc\FrontendApiBundle\Exception\RestException;
use Symfony\Component\HttpFoundation\Response;

class TwoFactorAuthDisabledException extends RestException
{
    public function __construct()
    {
        parent::__construct(RestCodeError::TWO_FACTOR_AUTH_DISABLED, Response::HTTP_BAD_REQUEST);
    }
}
