<?php

namespace Btc\FrontendApiBundle\Exception;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

class RestException extends HttpException
{
    public function __construct($message = null, $status = 0)
    {
        parent::__construct(Response::HTTP_OK, $message, null, [], $status);
    }
}
