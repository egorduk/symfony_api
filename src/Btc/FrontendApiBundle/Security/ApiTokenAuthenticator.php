<?php

namespace Btc\FrontendApiBundle\Security;

use Btc\CoreBundle\Entity\User;
use Btc\FrontendApiBundle\Exception\Rest\AccessDeniedException;
use Btc\FrontendApiBundle\Exception\Rest\UnknownErrorException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\PreAuthenticatedToken;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationFailureHandlerInterface;
use Symfony\Component\Security\Core\Authentication\SimplePreAuthenticatorInterface;

class ApiTokenAuthenticator implements SimplePreAuthenticatorInterface, AuthenticationFailureHandlerInterface
{
    public function createToken(Request $request, $providerKey)
    {
        $token = $request->headers->get('token');

        return $this->getPreAuthenticatedToken(new User(), ['ROLE_API'], $token);
    }

    public function supportsToken(TokenInterface $token, $providerKey)
    {
        return $token instanceof PreAuthenticatedToken && $token->getProviderKey() === $providerKey;
    }

    public function authenticateToken(TokenInterface $token, UserProviderInterface $userProvider, $providerKey)
    {
        if (!$userProvider instanceof ApiTokenUserProvider) {
            throw new UnknownErrorException();
        }

        $token = $token->getCredentials();

        if (!$token) {
            return $this->getPreAuthenticatedToken(new User(), ['IS_AUTHENTICATED_ANONYMOUSLY']);
        }

        $user = $userProvider->loadUserByUsername($token);

        if (is_null($user)) {
            throw new AccessDeniedException();
        }

        return $this->getPreAuthenticatedToken($user, array_merge(['ROLE_API'], $user->getRoles()), $token);
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception)
    {
        return new Response(
            strtr($exception->getMessageKey(), $exception->getMessageData()),
            Response::HTTP_UNAUTHORIZED
        );
    }

    private function getPreAuthenticatedToken($user, $roles, $token = '', $providerKey = 'rest_api_secured_area')
    {
        return new PreAuthenticatedToken(
            $user,
            $token,
            $providerKey,
            $roles
        );
    }
}
