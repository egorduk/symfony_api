<?php

namespace Btc\FrontendApiBundle\Service;

use Btc\CoreBundle\Entity\User;
use Btc\FrontendApiBundle\Events\AccountActivityEvents;
use Btc\FrontendApiBundle\Events\UserActivityEvent;
use Symfony\Component\EventDispatcher\Debug\TraceableEventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;

class ActivityLoggerService
{
    private $eventDispatcher;

    public function __construct(TraceableEventDispatcherInterface $eventDispatcher)
    {
        $this->eventDispatcher = $eventDispatcher;
    }

    public function logUserChangePassword(User $user, Request $request)
    {
        $this->eventDispatcher->dispatch(
            AccountActivityEvents::CHANGE_PASSWORD_COMPLETED,
            new UserActivityEvent($user, $request)
        );
    }

    public function logUserWithdraw(User $user, Request $request)
    {
        $this->eventDispatcher->dispatch(
            AccountActivityEvents::WITHDRAW_REQUEST,
            new UserActivityEvent($user, $request)
        );
    }

    public function logUserDeposit(User $user, Request $request)
    {
        $this->eventDispatcher->dispatch(
            AccountActivityEvents::DEPOSIT_REQUEST,
            new UserActivityEvent($user, $request)
        );
    }
}
