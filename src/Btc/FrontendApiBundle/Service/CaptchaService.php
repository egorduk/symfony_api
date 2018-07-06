<?php

namespace Btc\FrontendApiBundle\Service;

use Doctrine\Common\Cache\Cache;
use Symfony\Component\Security\Core\Authentication\Token\AnonymousToken;
use Symfony\Component\Security\Core\SecurityContext;
use Symfony\Component\Security\Core\SecurityContextInterface;
use Symfony\Component\HttpFoundation\Session\Session;

class CaptchaService
{
    const ATTEMPS_BEFORE_CAPTCHA = 5;
    const FAILED_LOGIN_ATTEMPTS = 'failed-login-attempts-';

    private $cache;
    private $securityContext;
    private $session;

    public function __construct(
        SecurityContextInterface $securityContext,
        Session $session,
        Cache $cache
    ) {
        $this->securityContext = $securityContext;
        $this->cache = $cache;
        $this->session = $session;
    }

    public function logFailedLoginAttempt()
    {
        if (!$this->securityContext->getToken() instanceof AnonymousToken) {
            $user = $this->securityContext->getToken()->getUser();
            $username = $user->getUsername();
        } else {
            $username = (null === $this->session) ? '' : $this->session->get(SecurityContext::LAST_USERNAME);
        }
        $cacheKey = self::FAILED_LOGIN_ATTEMPTS.$username;
        $failedAttempts = $this->cache->fetch($cacheKey) ?: 0;
        $this->cache->save($cacheKey, ++$failedAttempts, 1 * 60 * 60); //reset counter after 1hour
    }

    public function isFailedAttemptsLimitReached()
    {
        if (!$this->securityContext->getToken() instanceof AnonymousToken) {
            $user = $this->securityContext->getToken()->getUser();
            $username = $user->getUsername();
        } else {
            $username = (null === $this->session) ? '' : $this->session->get(SecurityContext::LAST_USERNAME);
        }
        $cacheKey = self::FAILED_LOGIN_ATTEMPTS.$username;
        $failedAttempts = $this->cache->fetch($cacheKey) ?: 0;
        if (!$username) {
            $failedAttempts = 0;
        }

        return $failedAttempts >= self::ATTEMPS_BEFORE_CAPTCHA;
    }
}
