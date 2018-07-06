<?php

namespace Btc\FrontendApiBundle\Exception\Rest;

use Btc\FrontendApiBundle\Classes\RestCodeError;
use Btc\FrontendApiBundle\Exception\RestException;
use Symfony\Component\HttpFoundation\Response;

class OrderAlreadyCancelledException extends RestException
{
    public function __construct()
    {
        parent::__construct(RestCodeError::ORDER_ALREADY_CANCELLED, Response::HTTP_BAD_REQUEST);
    }
}
