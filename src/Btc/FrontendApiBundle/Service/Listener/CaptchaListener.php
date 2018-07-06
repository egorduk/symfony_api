<?php

namespace Btc\FrontendApiBundle\Service\Listener;

use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\Extension\Csrf\CsrfProvider\CsrfProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Http\Firewall\UsernamePasswordFormAuthenticationListener;
use Symfony\Component\Security\Http\Authentication\AuthenticationFailureHandlerInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;
use Symfony\Component\Security\Http\Session\SessionAuthenticationStrategyInterface;
use Symfony\Component\Security\Http\HttpUtils;
use Symfony\Component\Security\Core\Authentication\AuthenticationManagerInterface;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;

class CaptchaListener extends UsernamePasswordFormAuthenticationListener
{
    private $csrfProvider;

    /**
     * {@inheritdoc}
     */
    public function __construct(
        TokenStorageInterface $securityContext,
        AuthenticationManagerInterface $authenticationManager,
        SessionAuthenticationStrategyInterface $sessionStrategy,
        HttpUtils $httpUtils,
        $providerKey,
        AuthenticationSuccessHandlerInterface $successHandler,
        AuthenticationFailureHandlerInterface $failureHandler,
        array $options = [],
        LoggerInterface $logger = null,
        EventDispatcherInterface $dispatcher = null,
        CsrfProviderInterface $csrfProvider = null
    ) {
        parent::__construct(
            $securityContext,
            $authenticationManager,
            $sessionStrategy,
            $httpUtils,
            $providerKey,
            $successHandler,
            $failureHandler,
            array_merge([
                'username_parameter' => '_username',
                'password_parameter' => '_password',
                'csrf_parameter' => '_csrf_token',
                'captcha' => 'captcha',
                'intention' => 'authenticate',
                'post_only' => true,
            ], $options),
            $logger,
            $dispatcher
        );

        $this->csrfProvider = $csrfProvider;
    }

    /**
     * {@inheritdoc}
     */
    protected function attemptAuthentication(Request $request)
    {
        $userCaptcha = $request->get($this->options['captcha'], null, true);
        $captcha = $request->getSession()->get('captcha');

        if ($captcha) {
            if ($userCaptcha !== $captcha->getPhrase()) {
                throw new BadCredentialsException('Invalid Captcha');
            }
        }

        return parent::attemptAuthentication($request);
    }
}
