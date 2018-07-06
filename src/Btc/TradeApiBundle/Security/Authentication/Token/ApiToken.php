<?php

namespace Btc\TradeApiBundle\Security\Authentication\Token;

use Btc\CoreBundle\Entity\ApiKey;
use Symfony\Component\Security\Core\Authentication\Token\AbstractToken;

class ApiToken extends AbstractToken
{
    private $apiKey;

    public function __construct(ApiKey $apiKey)
    {
        $this->apiKey = $apiKey;
        $this->setUser($user = $apiKey->getUser());

        parent::__construct($roles = array_merge(
            $user->getRoles(),
            $apiKey->getPermissions()
        ));

        parent::setAuthenticated(count($roles) > 0);
    }

    /**
     * {@inheritdoc}
     */
    public function setAuthenticated($isAuthenticated)
    {
        if ($isAuthenticated) {
            throw new \LogicException('Cannot set this token to trusted after instantiation.');
        }

        parent::setAuthenticated(false);
    }

    /**
     * {@inheritdoc}
     */
    public function eraseCredentials()
    {
        parent::eraseCredentials();
    }

    /**
     * {@inheritdoc}
     */
    public function getCredentials()
    {
        return $this->apiKey;
    }
}
