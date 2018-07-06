<?php

namespace Btc\FrontendApiBundle\Exception\Rest;

use Btc\FrontendApiBundle\Classes\RestCodeError;
use Btc\FrontendApiBundle\Exception\RestException;
use Symfony\Component\HttpFoundation\Response;

class OptimisticLockException extends RestException
{
    public function __construct()
    {
        parent::__construct(RestCodeError::OPTIMISTIC_LOCK_FAILED, Response::HTTP_BAD_REQUEST);
    }
}
