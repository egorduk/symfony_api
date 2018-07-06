<?php

namespace Btc\FrontendApiBundle\Events;

use Btc\CoreBundle\Entity\User;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\HttpFoundation\Request;

class UserActivityEvent extends Event
{
    private $user;
    private $request;
    private $params;

    public function __construct(User $user, Request $request, $params = [])
    {
        $this->user = $user;
        $this->request = $request;
        $this->params = $params;
    }

    public function getClientIp()
    {
        return $this->request->getClientIp();
    }

    public function getUser()
    {
        return $this->user;
    }

    public function getParams()
    {
        return $this->params;
    }
}
