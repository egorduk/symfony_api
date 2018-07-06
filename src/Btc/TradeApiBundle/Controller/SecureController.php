<?php

namespace Btc\TradeApiBundle\Controller;

use Btc\CoreBundle\Entity\ApiKey;
use FOS\RestBundle\Controller\Annotations;
use FOS\RestBundle\Controller\FOSRestController;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class SecureController extends FOSRestController
{
    const ALG = 'sha256';

    /**
     * Gets authorization token.
     *
     * ### Request URL example ###
     * GET /api/trade/v1/security/tokens?market=btc-usd
     * ### Success response example ###
     *     {
     *       "token": "qwerty1234"
     *     }
     * ### Error response example ###
     *     {
     *     },
     *
     * @ApiDoc(
     *   resource = true,
     *   description = "Gets authorization token",
     *   statusCodes = {
     *     Response::HTTP_OK = "Returned when successful"
     *   },
     *   section = "Trade"
     * )
     *
     * @Annotations\Post("/security/tokens")
     *
     * @Annotations\RequestParam(name="apiKey", requirements="\S+", description="Api key.")
     * @Annotations\RequestParam(name="apiSecret", requirements="\S+", description="Api secret.")
     *
     * @param Request $request
     *
     * @return Response
     *
     * @throws NotFoundHttpException when data do not exist
     */
    public function postTokenAction(Request $request)
    {
        $apiKey = $request->get('apiKey');
        $apiSecret = $request->get('apiSecret');

        $key = hash_pbkdf2(self::ALG, 'password', 'salt', 10000, 0, true);
        $mac_key = hash_hmac(self::ALG, 'mac', $key, true);
        $enc_key = hash_hmac(self::ALG, 'enc', $key, true);
        $enc_key = substr( $enc_key, 0, 32 );
        $temp = $nonce = (16 > 0 ? mcrypt_create_iv(16) : '');

        $payload = [
            'apiKey' => $apiKey,
            'apiSecret' => $apiSecret,
        ];
        $message = http_build_query($payload);

        $temp .= hash_hmac(self::ALG, $message, $mac_key, true);
        $temp .= hash_hmac(self::ALG, $message, $mac_key, true);
        $mac = hash_hmac(self::ALG, $temp, $mac_key, true);
        $siv = substr($mac, 0, 16);
        $enc = mcrypt_encrypt('rijndael-128', $enc_key, $message, 'ctr', $siv);

        $token = base64_encode($siv . $nonce . $enc);

        $user = $this->get('rest.repository.user')->find(1);
        $key = new ApiKey($user, true);
        $key->setKey($apiKey);
        $key->setSecret($apiSecret);
        $key->setCreatedAt(new \DateTime());
        $key->setUpdatedAt(new \DateTime());
        $key->setPermissions([ApiKey::PERM_TRADES]);

        $this->get('trade.api.repository.api_key')->save($key, true);

        return $this->handleView(
            $this->view([
                'token' => $token,
            ], Response::HTTP_OK)
        );
    }
}
