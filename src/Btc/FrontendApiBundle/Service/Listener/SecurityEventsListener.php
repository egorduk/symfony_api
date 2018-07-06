<?php

namespace Btc\FrontendApiBundle\Service\Listener;

use Btc\FrontendApiBundle\Events\AccountActivityEvents;
use Btc\FrontendApiBundle\Events\UserActivityEvent;
use Btc\FrontendApiBundle\Events\UserTradeActivityEvent;
use Btc\FrontendApiBundle\Service\UserActivityService;
use Doctrine\ORM\EntityManager;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\Security\Http\SecurityEvents;

class SecurityEventsListener implements EventSubscriberInterface
{
    private $activityLogger;
    private $em;

    public function __construct(UserActivityService $activityLogger, EntityManager $em)
    {
        $this->activityLogger = $activityLogger;
        $this->em = $em;
    }

    /**
     * Returns an array of event names this subscriber wants to listen to.
     *
     * @return array The event names to listen to
     *
     * TODO: fix unused events
     */
    public static function getSubscribedEvents()
    {
        return [
            SecurityEvents::INTERACTIVE_LOGIN => 'onLogin',
            AccountActivityEvents::CUSTOM_LOGIN => 'onCustomLogin',
            AccountActivityEvents::REGISTRATION_COMPLETED => 'onRegistrationComplete',
            AccountActivityEvents::CHANGE_PASSWORD_COMPLETED => 'onChangePassword',
            AccountActivityEvents::PROFILE_EDIT_COMPLETED => 'onProfileChange',
            AccountActivityEvents::TWO_FACTOR_ENABLED => 'onTwoFactorEnable',
            AccountActivityEvents::TWO_FACTOR_DISABLED => 'onTwoFactorDisable',
            AccountActivityEvents::LIMIT_BUY_ORDER => 'onLimitBuyDealSubmitted',
            AccountActivityEvents::LIMIT_SELL_ORDER => 'onLimitSellDealSubmitted',
            AccountActivityEvents::STOP_LIMIT_BUY_ORDER => 'onStopLimitBuyDealSubmitted',
            AccountActivityEvents::STOP_LIMIT_SELL_ORDER => 'onStopLimitSellDealSubmitted',
            AccountActivityEvents::MARKET_SELL_ORDER => 'onMarketSellDealSubmitted',
            AccountActivityEvents::MARKET_BUY_ORDER => 'onMarketBuyDealSubmitted',
            AccountActivityEvents::DEPOSIT_REQUEST => 'onDepositRequest',
            AccountActivityEvents::WITHDRAW_REQUEST => 'onWithdrawRequest',
            AccountActivityEvents::DEPOSIT => 'onDeposit',
            AccountActivityEvents::WITHDRAW => 'onWithdraw',
            AccountActivityEvents::PREFERENCES_UPDATED => 'onPreferencesUpdate',
            AccountActivityEvents::VOUCHER_REDEEM => 'onVoucherRedeem',
            AccountActivityEvents::VOUCHER_ISSUE => 'onVoucherIssue',
        ];
    }

    public function onLogin(InteractiveLoginEvent $event)
    {
        if ($user = $event->getAuthenticationToken()->getUser()) {
            if ($user->getId()) {
                if ($user->isActive()) {
                    return;
                }

                $user->setActive();

                $this->em->persist($user);
                $this->em->flush();

                $this->activityLogger->log(
                    $user,
                    AccountActivityEvents::LOGIN,
                    $event->getRequest()->getClientIp()
                );
            }
        }
    }

    public function onCustomLogin(UserActivityEvent $event) {
        $this->activityLogger->log(
            $event->getUser(),
            AccountActivityEvents::LOGIN,
            $event->getClientIp()
        );
    }

    public function onRegistrationComplete(UserActivityEvent $event)
    {
        $this->activityLogger->log(
            $event->getUser(),
            AccountActivityEvents::REGISTRATION_COMPLETED,
            $event->getClientIp()
        );
    }

    public function onChangePassword(UserActivityEvent $event)
    {
        $this->activityLogger->log(
            $event->getUser(),
            AccountActivityEvents::CHANGE_PASSWORD_COMPLETED,
            $event->getClientIp()
        );
    }

    public function onProfileChange(UserActivityEvent $event)
    {
        $this->activityLogger->log(
            $event->getUser(),
            AccountActivityEvents::PROFILE_EDIT_COMPLETED,
            $event->getClientIp()
        );
    }

    public function onTwoFactorEnable(UserActivityEvent $event)
    {
        $this->activityLogger->log(
            $event->getUser(),
            AccountActivityEvents::TWO_FACTOR_ENABLED,
            $event->getClientIp()
        );
    }

    public function onTwoFactorDisable(UserActivityEvent $event)
    {
        $this->activityLogger->log(
            $event->getUser(),
            AccountActivityEvents::TWO_FACTOR_DISABLED,
            $event->getClientIp()
        );
    }

    public function onLimitBuyDealSubmitted(UserTradeActivityEvent $event)
    {
        $this->activityLogger->log(
            $event->getUser(),
            AccountActivityEvents::LIMIT_BUY_ORDER,
            $event->getClientIp(),
            ['%amount%' => $event->getAmountWithCurrency(), '%price%' => $event->getPriceWithCurrency()]
        );
    }

    public function onLimitSellDealSubmitted(UserTradeActivityEvent $event)
    {
        $this->activityLogger->log(
            $event->getUser(),
            AccountActivityEvents::LIMIT_SELL_ORDER,
            $event->getClientIp(),
            ['%amount%' => $event->getAmountWithCurrency(), '%price%' => $event->getPriceWithCurrency()]
        );
    }

    public function onStopLimitBuyDealSubmitted(UserTradeActivityEvent $event)
    {
        $this->activityLogger->log(
            $event->getUser(),
            AccountActivityEvents::STOP_LIMIT_BUY_ORDER,
            $event->getClientIp(),
            ['%amount%' => $event->getAmountWithCurrency(), '%price%' => $event->getPriceWithCurrency()]
        );
    }

    public function onStopLimitSellDealSubmitted(UserTradeActivityEvent $event)
    {
        $this->activityLogger->log(
            $event->getUser(),
            AccountActivityEvents::STOP_LIMIT_SELL_ORDER,
            $event->getClientIp(),
            ['%amount%' => $event->getAmountWithCurrency(), '%price%' => $event->getPriceWithCurrency()]
        );
    }

    public function onMarketSellDealSubmitted(UserTradeActivityEvent $event)
    {
        $this->activityLogger->log(
            $event->getUser(),
            AccountActivityEvents::MARKET_SELL_ORDER,
            $event->getClientIp(),
            ['%amount%' => $event->getAmountWithCurrency()]
        );
    }

    public function onMarketBuyDealSubmitted(UserTradeActivityEvent $event)
    {
        $this->activityLogger->log(
            $event->getUser(),
            AccountActivityEvents::MARKET_BUY_ORDER,
            $event->getClientIp(),
            ['%amount%' => $event->getAmountWithCurrency()]
        );
    }

    public function onDepositRequest(UserActivityEvent $event)
    {
        $this->activityLogger->log(
            $event->getUser(),
            AccountActivityEvents::DEPOSIT_REQUEST,
            $event->getClientIp()
        );
    }

    public function onWithdrawRequest(UserActivityEvent $event)
    {
        $this->activityLogger->log(
            $event->getUser(),
            AccountActivityEvents::WITHDRAW_REQUEST,
            $event->getClientIp()
        );
    }

    public function onPreferencesUpdate(UserActivityEvent $event)
    {
        $this->activityLogger->log(
            $event->getUser(),
            AccountActivityEvents::PREFERENCES_UPDATED,
            $event->getClientIp()
        );
    }

    public function onWithdraw(UserActivityEvent $event)
    {
        $this->activityLogger->log(
            $event->getUser(),
            AccountActivityEvents::WITHDRAW,
            $event->getClientIp()
        );
    }

    public function onDeposit(UserActivityEvent $event)
    {
        $this->activityLogger->log(
            $event->getUser(),
            AccountActivityEvents::DEPOSIT,
            $event->getClientIp()
        );
    }

    public function onVoucherRedeem(UserActivityEvent $event)
    {
        $this->activityLogger->log(
            $event->getUser(),
            AccountActivityEvents::VOUCHER_REDEEM,
            $event->getClientIp(),
            $event->getParams()
        );
    }

    public function onVoucherIssue(UserActivityEvent $event)
    {
        $this->activityLogger->log(
            $event->getUser(),
            AccountActivityEvents::VOUCHER_ISSUE,
            $event->getClientIp(),
            $event->getParams()
        );
    }
}
