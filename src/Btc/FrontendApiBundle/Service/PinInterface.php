<?php

namespace Btc\FrontendApiBundle\Service;

interface PinInterface
{
    public function generate($length = 4);
    public function encodePin($raw, $salt);
    public function isPinValid($encoded, $raw, $salt);
}
