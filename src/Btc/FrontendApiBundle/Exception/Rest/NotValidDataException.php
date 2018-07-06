<?php

namespace Btc\FrontendApiBundle\Exception\Rest;

use Btc\FrontendApiBundle\Classes\RestCodeError;
use Btc\FrontendApiBundle\Exception\RestException;
use Symfony\Component\HttpFoundation\Response;

class NotValidDataException extends RestException
{
    public function __construct()
    {
        parent::__construct(RestCodeError::VALIDATION_ERROR, Response::HTTP_BAD_REQUEST);
    }
}
