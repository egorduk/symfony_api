<?php

namespace Btc\FrontendApiBundle\Exception\Rest;

use Btc\FrontendApiBundle\Classes\RestCodeError;
use Btc\FrontendApiBundle\Exception\RestException;
use Symfony\Component\HttpFoundation\Response;

class DbDefaultException extends RestException
{
    public function __construct()
    {
        parent::__construct(RestCodeError::DB_DEFAULT_ERROR, Response::HTTP_BAD_REQUEST);
    }
}
