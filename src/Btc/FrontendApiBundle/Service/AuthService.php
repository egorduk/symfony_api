<?php

namespace Btc\FrontendApiBundle\Service;

use Btc\FrontendApiBundle\Exception\Rest\InvalidTokenException;
use Namshi\JOSE\JWT;
use Namshi\JOSE\SimpleJWS;

class AuthService
{
    const ENCODE_ALGORITHM = 'RS256';

    protected $privateKeyPath = null;
    protected $publicKeyPath = null;
    private $tokenLifetime = 3600;

    /**
     * AuthService constructor.
     * @param string $privatePath
     * @param string $publicKeyPath
     * @param int $tokenLifetime
     */
    public function __construct($privatePath, $publicKeyPath, $tokenLifetime = 3600)
    {
        $this->privateKeyPath = $privatePath;
        $this->publicKeyPath = $publicKeyPath;
        $this->tokenLifetime = $tokenLifetime;
    }

    /**
     * @param int $userId
     *
     * @return string
     */
    public function getAuthToken($userId)
    {
        $jws = new SimpleJWS([
            'alg' => self::ENCODE_ALGORITHM,
            'typ' => 'JWT',
        ]);

        $jws->setPayload([
            'uid' => $userId,
            'exp' => (int)microtime(true) + $this->tokenLifetime,
        ]);

        $jws->sign($this->getPrivateKey());

        return $jws->getTokenString();
    }

    /**
     * @return bool|resource
     */
    private function getPrivateKey()
    {
        if (!is_readable($this->privateKeyPath)) {
            throw new InvalidTokenException();
        }

        $privateKey = file_get_contents($this->privateKeyPath);

        return openssl_pkey_get_private($privateKey); //decrypting the encrypted private key
    }

    /**
     * @return resource
     */
    private function getPublicKey()
    {
        if (!is_readable($this->publicKeyPath)) {
            throw new InvalidTokenException();
        }

        $publicKey = file_get_contents($this->publicKeyPath);

        return openssl_pkey_get_public($publicKey); //decrypting the encrypted private key
    }

    /**
     * @param $token
     * @return \Namshi\JOSE\JWS
     */
    public function getToken($token)
    {
        return SimpleJWS::load($token);
    }

    /**
     * @param JWT $jwsToken
     * @return mixed
     */
    public function checkToken(JWT $jwsToken)
    {
        return $jwsToken->isValid($this->getPublicKey(), self::ENCODE_ALGORITHM) && !$jwsToken->isExpired();
    }
}
