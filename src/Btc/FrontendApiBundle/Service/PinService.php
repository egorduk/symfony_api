<?php

namespace Btc\FrontendApiBundle\Service;

class PinService implements PinInterface
{
    /**
     * @param int $length
     *
     * @return string
     */
    public function generate($length = 4)
    {
        $pin = '';

        for ($i = 0; $i < $length; ++$i) {
            $pin .= mt_rand(0, 9);
        }

        return $pin;
    }

    public function encodePin($raw, $salt)
    {
        return hash('sha256', $salt.$raw);
    }

    public function isPinValid($encoded, $raw, $salt)
    {
        return $encoded === $this->encodePin($raw, $salt);
    }
}
