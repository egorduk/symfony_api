<?php

namespace Btc\FrontendApiBundle\Exception\Rest;

use Btc\FrontendApiBundle\Classes\RestCodeError;
use Btc\FrontendApiBundle\Exception\RestException;
use Symfony\Component\HttpFoundation\Response;

class SmsSendingException extends RestException
{
    public function __construct($message = '', $status = 0, $code = 0)
    {
        parent::__construct(RestCodeError::UNKNOWN_ERROR, Response::HTTP_INTERNAL_SERVER_ERROR);
    }
}
