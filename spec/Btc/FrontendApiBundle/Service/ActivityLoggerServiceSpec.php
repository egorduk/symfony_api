<?php

namespace spec\Btc\FrontendApiBundle\Service;

use Btc\CoreBundle\Entity\User;
use Btc\FrontendApiBundle\Events\AccountActivityEvents;
use Btc\FrontendApiBundle\Events\UserActivityEvent;
use Btc\FrontendApiBundle\Service\ActivityLoggerService;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Symfony\Component\EventDispatcher\Debug\TraceableEventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;

class ActivityLoggerServiceSpec extends ObjectBehavior
{
    public function let(TraceableEventDispatcherInterface $eventDispatcher)
    {
        $this->beConstructedWith($eventDispatcher);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(ActivityLoggerService::class);
    }

    public function it_should_log_user_change_password_event(User $user, Request $request, TraceableEventDispatcherInterface $eventDispatcher)
    {
        $eventDispatcher->dispatch(
            AccountActivityEvents::CHANGE_PASSWORD_COMPLETED,
            Argument::type(UserActivityEvent::class)
        )->shouldBeCalled();

        $this->logUserChangePassword($user, $request);
    }

    public function it_should_log_user_withdraw_event(User $user, Request $request, TraceableEventDispatcherInterface $eventDispatcher)
    {
        $eventDispatcher->dispatch(
            AccountActivityEvents::WITHDRAW_REQUEST,
            Argument::type(UserActivityEvent::class)
        )->shouldBeCalled();

        $this->logUserWithdraw($user, $request);
    }

    public function it_should_log_user_deposit_event(User $user, Request $request, TraceableEventDispatcherInterface $eventDispatcher)
    {
        $eventDispatcher->dispatch(
            AccountActivityEvents::DEPOSIT_REQUEST,
            Argument::type(UserActivityEvent::class)
        )->shouldBeCalled();

        $this->logUserDeposit($user, $request);
    }
}
