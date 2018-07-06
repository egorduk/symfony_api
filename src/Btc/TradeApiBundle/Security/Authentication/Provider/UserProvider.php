<?php

namespace Btc\TradeApiBundle\Security\Authentication\Provider;

use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;

class UserProvider implements UserProviderInterface
{
    public function loadUserByUsername($username)
    {
        throw new UsernameNotFoundException("User provider cannot and should not be used..");
    }

    public function refreshUser(UserInterface $user)
    {
        throw new UsernameNotFoundException("Refresh user method is not supported, session is not used..");
    }

    public function supportsClass($class)
    {
        return true;
    }
}
