<?php

namespace Btc\FrontendApiBundle\Service;

use Btc\CoreBundle\Entity\Bank;
use Btc\CoreBundle\Entity\Verification;
use Btc\FrontendApiBundle\Repository\SettingsRepository;
use Exmarkets\PaymentCoreBundle\Gateway\Model\DepositModel;
use Exmarkets\PaymentCoreBundle\Gateway\Model\WithdrawModel;

class NotificationsService
{
    private $email;
    private $settings;

    public function __construct(EmailSenderService $email, SettingsRepository $settings)
    {
        $this->email = $email;
        $this->settings = $settings;
    }

    public function notifyAboutVerification(Verification $verification)
    {
        if ($this->settings->isNotificationEnabled('verification')) {
            $this->email->sendVerificationNotificationEmail($verification);
        }
    }

    public function notifyAboutWithdraw(WithdrawModel $withdraw, Bank $bank)
    {
        if ($this->settings->isNotificationEnabled('withdraw')) {
            $this->email->sendWithdrawNotificationEmail($withdraw, $bank, $withdraw->getCurrency());
        }
    }
}
