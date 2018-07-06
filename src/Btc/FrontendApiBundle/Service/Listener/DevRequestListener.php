<?php

namespace Btc\FrontendApiBundle\Service\Listener;

use Btc\FrontendApiBundle\Repository\UserRepository;
use Symfony\Component\Security\Core\Authentication\Token\PreAuthenticatedToken;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class DevRequestListener
{
    private $isDebugMode;
    private $userId;
    private $security;
    private $userRepository;

    public function __construct($isDebugMode, $userId, TokenStorageInterface $security, UserRepository $userRepository)
    {
        $this->isDebugMode = $isDebugMode;
        $this->userId = $userId;
        $this->security = $security;
        $this->userRepository = $userRepository;
    }

    public function onKernelRequest()
    {
        if (!$this->isDebugMode || !$this->userId) {
            return;
        }

        $user = $this->userRepository->find($this->userId);

        if (!$user) {
            return;
        }

        $token = new PreAuthenticatedToken($user, null, 'rest_api_secured_area', ['ROLE_USER']);
        $this->security->setToken($token);
    }
}
