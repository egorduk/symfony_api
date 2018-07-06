<?php

namespace Btc\FrontendApiBundle\Security;

use Btc\FrontendApiBundle\Exception\Rest\InvalidCredentialsException;
use Btc\FrontendApiBundle\Exception\Rest\InvalidTokenException;
use Btc\FrontendApiBundle\Service\AuthService;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Core\User\User;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;

class ApiTokenUserProvider implements UserProviderInterface
{
    private $em;

    private $authService;

    public function __construct(EntityManager $em, AuthService $authService)
    {
        $this->em = $em;
        $this->authService = $authService;
    }

    /**
     * @param string $token
     *
     * @return User|null
     */
    public function loadUserByUsername($token)
    {
        $jwsToken = $this->authService->getToken($token);

        $id = null;

        if ($this->authService->checkToken($jwsToken) && ($payload = $jwsToken->getPayload())) {
            $id = $payload['uid'] ? $payload['uid'] : null;
        }

        if (!$id) {
            throw new InvalidTokenException();
        }

        return $this->em
            ->createQueryBuilder()
            ->select('u')
            ->from(\Btc\CoreBundle\Entity\User::class, 'u')
            ->where('u.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @param UserInterface $user
     *
     * @throws UnsupportedUserException
     */
    public function refreshUser(UserInterface $user)
    {
        throw new UnsupportedUserException();
    }

    /**
     * @param string $class
     *
     * @return bool
     */
    public function supportsClass($class)
    {
        return User::class === $class;
    }
}
