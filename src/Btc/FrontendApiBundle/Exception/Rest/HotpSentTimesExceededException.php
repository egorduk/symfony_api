<?php

namespace Btc\FrontendApiBundle\Exception\Rest;

use Btc\FrontendApiBundle\Classes\RestCodeError;
use Btc\FrontendApiBundle\Exception\RestException;
use Symfony\Component\HttpFoundation\Response;

class HotpSentTimesExceededException extends RestException
{
    public function __construct()
    {
        parent::__construct(RestCodeError::HOTP_SENT_TIMES_EXCEEDED, Response::HTTP_BAD_REQUEST);
    }
}
