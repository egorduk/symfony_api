<?php

namespace spec\Btc\FrontendApiBundle\Security;

use Btc\FrontendApiBundle\Security\ApiTokenAuthenticator;
use Btc\FrontendApiBundle\Security\ApiTokenUserProvider;
use PhpSpec\ObjectBehavior;
use Symfony\Component\Security\Core\Authentication\SimplePreAuthenticatorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationFailureHandlerInterface;

class ApiTokenAuthenticatorSpec extends ObjectBehavior
{
    const PROVIDER_NAME = 'rest_api_secured_area';

    public function it_should_extend_from_abstract_token()
    {
        $this->shouldHaveType(ApiTokenAuthenticator::class);
        $this->shouldImplement(SimplePreAuthenticatorInterface::class);
        $this->shouldImplement(AuthenticationFailureHandlerInterface::class);
    }

    public function it_should_get_authenticate_token(TokenInterface $token, ApiTokenUserProvider $apiTokenUserProvider, UserInterface $user)
    {
        $token->getCredentials()->willReturn($token);

        $apiTokenUserProvider->loadUserByUsername($token)->willReturn($user)->shouldBeCalled();

        $user->getRoles()->willReturn([]);

        $this->authenticateToken($token, $apiTokenUserProvider, self::PROVIDER_NAME);
    }

    public function it_should_throw_access_denied_exception_if_user_is_not_found(TokenInterface $token, ApiTokenUserProvider $apiTokenUserProvider)
    {
        $token->getCredentials()->willReturn($token);

        $apiTokenUserProvider->loadUserByUsername($token)->willReturn(null);

        $this
            ->shouldThrow(AccessDeniedException::class)
            ->duringAuthenticateToken($token, $apiTokenUserProvider, self::PROVIDER_NAME);
    }
}
