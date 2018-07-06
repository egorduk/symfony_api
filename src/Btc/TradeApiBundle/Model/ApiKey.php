<?php

namespace Btc\TradeApiBundle\Model;

use Btc\CoreBundle\Entity\User;

class ApiKey
{
    const PERM_TRADES = 'ROLE_TRADES';
    const PERM_ACCOUNT = 'ROLE_ACCOUNT';

    public static $permissionMap = [
        self::PERM_TRADES => 'trades',
        self::PERM_ACCOUNT => 'account',
    ];

    public static $roleMap = [
        'ROLE_TIER_ENSIGN' => 'ensign',
        'ROLE_TIER_LIEUTENANT' => 'lieutenant',
        'ROLE_TIER_COMMANDER' => 'commander',
    ];

    private $key;
    private $secret;
    private $user;
    private $permissions;
    private $active;

    public function __construct(array $data = [])
    {
        foreach ($data as $fieldName => $value) {
            $this->{$fieldName} = $value;
        }
    }

    public function getKey()
    {
        return $this->key;
    }

    public function getSecret()
    {
        return $this->secret;
    }

    public function setUser(User $user)

    {
        $this->user = $user;
        return $this;
    }

    public function getUser()
    {
        return $this->user;
    }

    public function getPermissions()
    {
        return $this->permissions;
    }

    public function isActive()
    {
        return $this->active;
    }
}
