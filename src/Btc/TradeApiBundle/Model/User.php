<?php

namespace Btc\ApiBundle\Model;

class User
{
    private $id;

    private $username;

    private $email;

    private $roles;

    private $authKey;

    public function __construct(array $data = [])
    {
        foreach ($data as $fieldName => $value) {
            $this->{$fieldName} = $value;
        }
    }

    public function getId()
    {
        return $this->id;
    }

    public function getUsername()
    {
        return $this->username;
    }

    public function getEmail()
    {
        return $this->email;
    }

    public function getRoles()
    {
        return $this->roles;
    }

    public function isBlocked()
    {
        return in_array('ROLE_BLOCKED', $this->roles, true);
    }

    public function getAuthKey()
    {
        return $this->authKey;
    }

    public function __toString()
    {
        return $this->username;
    }
}
