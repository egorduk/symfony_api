<?php

namespace Btc\FrontendApiBundle\Exception\Rest;

use Btc\FrontendApiBundle\Classes\RestCodeError;
use Btc\FrontendApiBundle\Exception\RestException;
use Symfony\Component\HttpFoundation\Response;

class AccessDeniedException extends RestException
{
    public function __construct()
    {
        parent::__construct(RestCodeError::ACCESS_DENIED, Response::HTTP_FORBIDDEN);
    }
}
