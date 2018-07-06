<?php

namespace Btc\TradeApiBundle\Security\Authentication\Provider;

use Btc\FrontendApiBundle\Exception\Rest\AccessDeniedException;
use Btc\TradeApiBundle\Controller\SecureController;
use Btc\TradeApiBundle\Exception\AuthenticationHttpException;
use Btc\TradeApiBundle\Security\Authentication\Token\ApiToken;
use Btc\TradeApiBundle\Security\Authentication\Token\PreAuthToken;
use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\DBAL\DBALException;
use Symfony\Component\Security\Core\Authentication\SimplePreAuthenticatorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\User\UserProviderInterface;

class AuthenticationAuthenticator implements SimplePreAuthenticatorInterface
{
    private $apiKeyRepository;
    private $apiNonceRepository;


    public function __construct(ObjectRepository $apiKeyRepository, ObjectRepository $apiNonceRepository)
    {
        $this->apiKeyRepository = $apiKeyRepository;
        $this->apiNonceRepository = $apiNonceRepository;
    }

    public function createToken(Request $request, $providerKey)
    {
        if (!$request->headers->has('Authorization')) {
            throw new AuthenticationHttpException('Authorization header is missing.');
        }

        $header = $request->headers->get('Authorization');

        if (strlen($header) < 6) {
            throw new AuthenticationHttpException('Authorization header is too short.');
        }

        $key = hash_pbkdf2(SecureController::ALG, 'password', 'salt', 10000, 0, true);
        $enc_key = hash_hmac(SecureController::ALG, 'enc', $key, true);
        $enc_key = substr($enc_key, 0, 32);
        $enc = base64_decode($header);
        $siv = substr($enc, 0, 16);
        $enc = substr($enc, 16 + 16);
        $data = mcrypt_decrypt('rijndael-128', $enc_key, $enc, 'ctr', $siv);

        parse_str($data, $parsedData);

        if (!array_key_exists('apiKey', $parsedData) || !array_key_exists('apiSecret', $parsedData)) {
            throw new AccessDeniedException();
        }

        $key = $parsedData['apiKey'];
        $signature = $parsedData['apiSecret'];

        return new PreAuthToken($key, $signature, $request);
    }

    public function authenticateToken(TokenInterface $token, UserProviderInterface $userProvider, $providerKey)
    {
        $key = $token->getCredentials();
        $apiKey = $this->apiKeyRepository->findOneWithUserByKey($key);

        if (!$apiKey) {
            throw new AuthenticationHttpException("API key: '{$key}' could not be found");
        }

        if (!$apiKey->isActive()) {
            throw new AuthenticationHttpException("API key: '{$key}' is not activated");
        }

        if ($apiKey->getUser()->isBlocked()) {
            throw new AuthenticationHttpException("API key: '{$key}' owner is blocked");
        }

        // Check if nonce is available in request
        if (!$nonce = $token->getNonce()) {
            throw new AuthenticationHttpException("Nonce must be provided in request.");
        }

        // Mark nonce as used, will throw an exception in case if it was already used
        try {
            $this->apiNonceRepository->persistNonceForApiKey($nonce, $apiKey);
        } catch (DBALException $e) {
            throw new AuthenticationHttpException('The nonce provided in request was already used.', $e);
        }

        return new ApiToken($apiKey);
    }

    public function supportsToken(TokenInterface $token, $providerKey)
    {
        return $token instanceof PreAuthToken /*&& $token->getProviderKey() === $providerKey*/;
    }
}
