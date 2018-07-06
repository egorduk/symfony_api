<?php

namespace Btc\FrontendApiBundle\Exception\Rest;

use Btc\FrontendApiBundle\Classes\RestCodeError;
use Btc\FrontendApiBundle\Exception\RestException;
use Symfony\Component\HttpFoundation\Response;

class NotFoundException extends RestException
{
    public function __construct()
    {
        parent::__construct(RestCodeError::NOT_FOUND, Response::HTTP_NOT_FOUND);
    }
}
