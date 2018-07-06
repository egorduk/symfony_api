<?php

namespace Btc\FrontendApiBundle\Exception\Rest;

use Btc\FrontendApiBundle\Classes\RestCodeError;
use Btc\FrontendApiBundle\Exception\RestException;
use Symfony\Component\HttpFoundation\Response;

class AlreadyExistsException extends RestException
{
    public function __construct()
    {
        parent::__construct(RestCodeError::ALREADY_EXISTS, Response::HTTP_BAD_REQUEST);
    }
}
