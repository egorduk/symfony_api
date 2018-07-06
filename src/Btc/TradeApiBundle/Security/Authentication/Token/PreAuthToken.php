<?php

namespace Btc\TradeApiBundle\Security\Authentication\Token;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\AbstractToken;

class PreAuthToken extends AbstractToken
{
    private $key;
    private $signature;
    private $request;
    private $params;

    public function __construct($key, $signature, Request $request)
    {
        $this->key = $key;
        $this->signature = $signature;
        $this->params = $this->parseRequestParams($request);
        $this->request = $request;

        parent::__construct([]); // no roles
    }

    public function getNonce()
    {
        return isset($this->params['nonce']) ? $this->params['nonce'] : null;
    }

    /**
     * {@inheritdoc}
     */
    public function getCredentials()
    {
        return $this->key;
    }

    public function getRequest()
    {
        return $this->request;
    }

    /**
     * Parses params from get; post and raw post
     *
     * @param Request $request
     * @return array
     */
    private function parseRequestParams(Request $request)
    {
        $params = array_merge($request->query->all(), $request->request->all());
        parse_str($request->getContent(), $output);

        return array_merge($params, $output);
    }
}
