<?php

namespace spec\Btc\FrontendApiBundle\Service;

use Btc\CoreBundle\Entity\Bank;
use Btc\CoreBundle\Entity\Currency;
use Btc\CoreBundle\Entity\Verification;
use Btc\FrontendApiBundle\Repository\SettingsRepository;
use Btc\FrontendApiBundle\Service\EmailSenderService;
use Btc\FrontendApiBundle\Service\NotificationsService;
use Exmarkets\PaymentCoreBundle\Gateway\Model\DepositModel;
use Exmarkets\PaymentCoreBundle\Gateway\Model\WithdrawModel;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class NotificationsServiceSpec extends ObjectBehavior
{
    public function let(EmailSenderService $mailer, SettingsRepository $settings)
    {
        $this->beConstructedWith($mailer, $settings);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(NotificationsService::class);
    }

    public function it_should_send_verify_notification_if_it_is_enabled(EmailSenderService $mailer, SettingsRepository $settings, Verification $verification)
    {
        $settings->isNotificationEnabled('verification')->shouldBeCalled()->willReturn(true);

        $mailer->sendVerificationNotificationEmail(Argument::any())->shouldBeCalled();

        $this->notifyAboutVerification($verification);
    }

    public function it_should_not_send_verify_notification_if_it_is_disabled(EmailSenderService $mailer, SettingsRepository $settings, Verification $verification)
    {
        $settings->isNotificationEnabled('verification')->shouldBeCalled()->willReturn(false);

        $mailer->sendVerificationNotificationEmail(Argument::any())->shouldNotBeCalled();

        $this->notifyAboutVerification($verification);
    }

    public function it_should_send_deposit_notification_if_it_is_enabled(
        EmailSenderService $mailer,
        SettingsRepository $settings,
        DepositModel $deposit,
        Bank $bank,
        Currency $currency
    ) {
        $settings->isNotificationEnabled('deposit')->shouldBeCalled()->willReturn(true);

        $deposit->getCurrency()->willReturn($currency);

        $mailer->sendDepositNotificationEmail(
            Argument::type(Deposit::class),
            Argument::type(Bank::class),
            Argument::type(Currency::class)
        )->shouldBeCalled();

        $this->notifyAboutDeposit($deposit, $bank);
    }

    public function it_should_not_send_deposit_notification_if_it_is_disabled(
        EmailSenderService $mailer,
        SettingsRepository $settings,
        DepositModel $deposit,
        Bank $bank,
        Currency $currency
    ) {
        $settings->isNotificationEnabled('deposit')->shouldBeCalled()->willReturn(false);

        $deposit->getCurrency()->willReturn($currency);

        $mailer->sendDepositNotificationEmail(
            Argument::type(DepositModel::class),
            Argument::type(Bank::class),
            Argument::type(Currency::class)
        )->shouldNotBeCalled();

        $this->notifyAboutDeposit($deposit, $bank);
    }

    public function it_should_send_withdrawal_notification_if_it_is_enabled(
        EmailSenderService $mailer,
        SettingsRepository $settings,
        WithdrawModel $withdrawal,
        Bank $bank,
        Currency $currency
    ) {
        $settings->isNotificationEnabled('withdrawal')->shouldBeCalled()->willReturn(true);

        $withdrawal->getCurrency()->willReturn($currency);

        $mailer->sendWithdrawNotificationEmail(
            Argument::type(WithdrawModel::class),
            Argument::type(Bank::class),
            Argument::type(Currency::class)
        )->shouldBeCalled();

        $this->notifyAboutWithdrawal($withdrawal, $bank);
    }

    public function it_should_send_withdrawal_notification_if_it_is_disabled(
        EmailSenderService $mailer,
        SettingsRepository $settings,
        WithdrawModel $withdrawal,
        Bank $bank,
        Currency $currency
    ) {
        $settings->isNotificationEnabled('withdrawal')->shouldBeCalled()->willReturn(false);

        $withdrawal->getCurrency()->willReturn($currency);

        $mailer->sendWithdrawNotificationEmail(
            Argument::type(WithdrawModel::class),
            Argument::type(Bank::class),
            Argument::type(Currency::class)
        )->shouldNotBeCalled();

        $this->notifyAboutWithdrawal($withdrawal, $bank);
    }
}
