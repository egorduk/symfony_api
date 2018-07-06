<?php

namespace Btc\ApiBundle\Model;

class Currency
{
    private $id;

    private $code;

    private $crypto;

    private $isErcToken;

    private $contractAbi;

    private $contractAddress;

    public function __construct(array $data = [])
    {
        foreach ($data as $fieldName => $value) {
            $this->{$fieldName} = $value;
        }
    }

    public function getId()
    {
        return $this->id;
    }

    public function getCode()
    {
        return $this->code;
    }

    public function isCrypto()
    {
        return $this->crypto;
    }
}
