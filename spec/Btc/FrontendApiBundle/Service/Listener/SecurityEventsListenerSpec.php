<?php

namespace spec\Btc\FrontendApiBundle\Service\Listener;

use Btc\CoreBundle\Entity\User;
use Btc\FrontendApiBundle\Events\AccountActivityEvents;
use Btc\FrontendApiBundle\Events\UserActivityEvent;
use Btc\FrontendApiBundle\Events\UserTradeActivityEvent;
use Btc\FrontendApiBundle\Service\Listener\SecurityEventsListener;
use Btc\FrontendApiBundle\Service\UserActivityService;
use Btc\CoreBundle\Model\LoggableActivityInterface;
use Doctrine\ORM\EntityManager;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\AbstractToken;
use Symfony\Component\Security\Core\Authentication\Token\PreAuthenticatedToken;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Symfony\Component\Security\Http\SecurityEvents;

class SecurityEventsListenerSpec extends ObjectBehavior
{
    private $logger;

    public function getMatchers()
    {
        return [
            'haveEvent' => function ($subject, $value) {
                return array_key_exists($value, $subject);
            },
        ];
    }

    public function let(
        UserActivityService $logger,
        EntityManager $em,
        InteractiveLoginEvent $loginEvent,
        AbstractToken $token,
        LoggableActivityInterface $target,
        UserActivityEvent $userActivityEvent,
        Request $request
    ) {
        $request->getClientIp()->willReturn('127.0.0.1');
        $token->getUser()->willReturn($target);
        $loginEvent->getAuthenticationToken()->willReturn($token);
        $loginEvent->getRequest()->willReturn($request);
        $userActivityEvent->getUser()->willReturn($target);
        $userActivityEvent->getClientIp()->willReturn('127.1.1.1');

        $this->logger = $logger;

        $this->beConstructedWith($logger, $em);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(SecurityEventsListener::class);
        $this->shouldHaveType(EventSubscriberInterface::class);
    }

    public function it_should_listen_for_events_that_must_be_logged()
    {
        $this->getSubscribedEvents()->shouldHaveEvent(SecurityEvents::INTERACTIVE_LOGIN);
        $this->getSubscribedEvents()->shouldHaveEvent(AccountActivityEvents::REGISTRATION_COMPLETED);
        $this->getSubscribedEvents()->shouldHaveEvent(AccountActivityEvents::CHANGE_PASSWORD_COMPLETED);
        $this->getSubscribedEvents()->shouldHaveEvent(AccountActivityEvents::PROFILE_EDIT_COMPLETED);
        $this->getSubscribedEvents()->shouldHaveEvent(AccountActivityEvents::TWO_FACTOR_ENABLED);
        $this->getSubscribedEvents()->shouldHaveEvent(AccountActivityEvents::TWO_FACTOR_DISABLED);
        $this->getSubscribedEvents()->shouldHaveEvent(AccountActivityEvents::LIMIT_BUY_ORDER);
        $this->getSubscribedEvents()->shouldHaveEvent(AccountActivityEvents::LIMIT_SELL_ORDER);
        $this->getSubscribedEvents()->shouldHaveEvent(AccountActivityEvents::MARKET_SELL_ORDER);
        $this->getSubscribedEvents()->shouldHaveEvent(AccountActivityEvents::MARKET_BUY_ORDER);
        $this->getSubscribedEvents()->shouldHaveEvent(AccountActivityEvents::DEPOSIT_REQUEST);
        $this->getSubscribedEvents()->shouldHaveEvent(AccountActivityEvents::WITHDRAW_REQUEST);
        $this->getSubscribedEvents()->shouldHaveEvent(AccountActivityEvents::PREFERENCES_UPDATED);
    }

    public function it_should_log_login_event(
        InteractiveLoginEvent $event,
        User $user,
        PreAuthenticatedToken $preAuthenticatedToken,
        Request $request
    ) {
        $event->getAuthenticationToken()->willReturn($preAuthenticatedToken);

        $preAuthenticatedToken->getUser()->willReturn($user);

        $user->getId()->willReturn(1);
        $user->isActive()->willReturn(false);
        $user->setActive()->shouldBeCalled();

        $event->getRequest()->willReturn($request);

        $this->assertEventHasBeenLogged(AccountActivityEvents::LOGIN);

        $this->onLogin($event);
    }

    public function it_should_log_registration_event(UserActivityEvent $userActivityEvent)
    {
        $this->assertEventHasBeenLogged(AccountActivityEvents::REGISTRATION_COMPLETED);

        $this->onRegistrationComplete($userActivityEvent);
    }

    public function it_should_log_password_change(UserActivityEvent $userActivityEvent)
    {
        $this->assertEventHasBeenLogged(AccountActivityEvents::CHANGE_PASSWORD_COMPLETED);

        $this->onChangePassword($userActivityEvent);
    }

    public function it_should_log_profile_edit(UserActivityEvent $userActivityEvent)
    {
        $this->assertEventHasBeenLogged(AccountActivityEvents::PROFILE_EDIT_COMPLETED);
        $this->onProfileChange($userActivityEvent);
    }

    public function it_should_log_two_factor_enable(UserActivityEvent $userActivityEvent)
    {
        $this->assertEventHasBeenLogged(AccountActivityEvents::TWO_FACTOR_ENABLED);
        $this->onTwoFactorEnable($userActivityEvent);
    }

    public function it_should_log_two_factor_disable(UserActivityEvent $userActivityEvent)
    {
        $this->assertEventHasBeenLogged(AccountActivityEvents::TWO_FACTOR_DISABLED);
        $this->onTwoFactorDisable($userActivityEvent);
    }

    public function it_should_log_limit_buy_deal_submitted(UserTradeActivityEvent $event, LoggableActivityInterface $target)
    {
        $event->getAmountWithCurrency()->willReturn('1 BTC');
        $event->getPriceWithCurrency()->willReturn('100 USD');

        $event->getUser()->willReturn($target);
        $event->getClientIp()->willReturn('127.0.0.1');

        $this->logger->log(
            Argument::type(LoggableActivityInterface::class),
            AccountActivityEvents::LIMIT_BUY_ORDER,
            '127.0.0.1',
            ['%amount%' => '1 BTC', '%price%' => '100 USD']
        )->shouldBeCalled();

        $this->onLimitBuyDealSubmitted($event);
    }

    public function it_should_log_limit_sell_deal_submitted(UserTradeActivityEvent $event, LoggableActivityInterface $target)
    {
        $event->getAmountWithCurrency()->willReturn('1 BTC');
        $event->getPriceWithCurrency()->willReturn('100 USD');

        $event->getUser()->willReturn($target);
        $event->getClientIp()->willReturn('127.0.0.1');

        $this->logger->log(
            Argument::type(LoggableActivityInterface::class),
            AccountActivityEvents::LIMIT_SELL_ORDER,
            '127.0.0.1',
            ['%amount%' => '1 BTC', '%price%' => '100 USD']
        )->shouldBeCalled();

        $this->onLimitSellDealSubmitted($event);
    }

    public function it_should_log_instant_sell_deal_submitted(UserTradeActivityEvent $event, LoggableActivityInterface $target)
    {
        $event->getAmountWithCurrency()->willReturn('1 BTC');

        $event->getUser()->willReturn($target);
        $event->getClientIp()->willReturn('127.0.0.1');

        $this->logger->log(
            Argument::type(LoggableActivityInterface::class),
            AccountActivityEvents::MARKET_SELL_ORDER,
            '127.0.0.1',
            ['%amount%' => '1 BTC']
        )->shouldBeCalled();

        $this->onMarketSellDealSubmitted($event);
    }

    public function it_should_log_instant_buy_deal_submitted(UserTradeActivityEvent $event, LoggableActivityInterface $target)
    {
        $event->getAmountWithCurrency()->willReturn('10 BTC');

        $event->getUser()->willReturn($target);
        $event->getClientIp()->willReturn('127.0.0.1');

        $this->logger->log(
            Argument::type(LoggableActivityInterface::class),
            AccountActivityEvents::MARKET_BUY_ORDER,
            '127.0.0.1',
            ['%amount%' => '10 BTC']
        )->shouldBeCalled();

        $this->onMarketBuyDealSubmitted($event);
    }

    private function assertEventHasBeenLogged($event)
    {
        $this->logger->log(
            Argument::type(LoggableActivityInterface::class),
            $event,
            Argument::any()
        )->shouldBeCalled();
    }
}
