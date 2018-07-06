<?php

namespace Btc\FrontendApiBundle\Service;

class RestRedis extends \Redis
{
    public function __construct($host, $port, $dbNumber = 0)
    {
        $this->connect($host, $port);
        $this->select((int) $dbNumber);
    }
}
