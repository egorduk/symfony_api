<?php

namespace Btc\FrontendApiBundle\Service;

use GuzzleHttp\Client;

class HttpClientService extends Client
{
    public function __construct($orderBookBaseUrl = '')
    {
        parent::__construct(['base_uri' => $orderBookBaseUrl]);
    }
}
