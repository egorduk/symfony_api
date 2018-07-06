<?php

namespace Btc\TradeApiBundle\Exception;

use Symfony\Component\HttpKernel\Exception\HttpException;

class AuthenticationHttpException extends HttpException
{
    public function __construct($message = null, \Exception $previous = null)
    {
        parent::__construct(401, $message, $previous);
    }
}
