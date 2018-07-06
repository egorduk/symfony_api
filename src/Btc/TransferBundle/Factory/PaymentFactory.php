<?php

namespace Btc\TransferBundle\Factory;

use Btc\TransferBundle\Exception\UnknownBankException;
use Btc\Component\Market\Service\FeeService;
use Btc\Component\Market\Model\PaymentFeeCollection;
use Btc\TransferBundle\Service\PaymentLimitService;
use Btc\CoreBundle\Entity;
use Exmarkets\PaymentCoreBundle\Gateway\Model;
use Btc\TransferBundle\Form\Type;
use Btc\FrontendApiBundle\Repository\CountryRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\SecurityContextInterface;

class PaymentFactory
{
    private $security;
    private $paymentLimits;
    private $fees;
    private $countries;

    public function __construct(
        CountryRepository $countries,
        PaymentLimitService $limits,
        SecurityContextInterface $security,
        FeeService $fees
    ) {
        $this->countries = $countries;
        $this->paymentLimits = $limits;
        $this->security = $security;
        $this->fees = $fees->getPaymentFees();
    }

    public function withdrawalModel(Entity\Bank $bank, Request $request = null)
    {
        $limits = $this->paymentLimits->withdrawals(
            $user = $this->security->getToken()->getUser(),
            $bank->getFiat() ? Entity\Currency::FIAT : Entity\Currency::VIRTUAL
        );
        $fees = new PaymentFeeCollection($this->fees->getWithdrawalFeesByBankId($bank->getId()));
        switch ($bank->getSlug()) {
            case 'international-wire-transfer':
                $withdrawal = new Model\InternationalWireWithdrawal($user, $limits, $fees);
                break;
            //case 'egopay':
            //case 'payza':
            //case 'okpay':
            //case 'perfect-money':
            //    $withdrawal = new Model\WithdrawalModel($user, $limits, $fees);
            //    break;
            case 'btc':
            case 'ltc':
            case 'eth':
            case 'bnk':
                $withdrawal = new Model\VirtualWithdrawal($user, $limits, $fees);
                break;
            //case 'paypal':
            //case 'moneygram':
            //case 'westernunion':
            //    $withdrawal = new Model\ManualWithdrawal($user, $limits, $fees);
            //    // this feature could possible be for all withdrawal models
            //    if ($request !== null) {
            //        $withdrawal->setIp($request->getClientIp());
            //    }
            //    break;
            default:
                throw new UnknownBankException("Bank '{$bank->getSlug()}' is not available for withdrawals.");
        }
        $withdrawal->setBankId($bank->getId());
        $withdrawal->setPaymentMethod($bank->getPaymentMethod());

        return $withdrawal;
    }

    public function depositModel(Entity\Bank $bank)
    {
        $limits = $this->paymentLimits->deposits(
            $user = $this->security->getToken()->getUser(),
            $bank->getFiat() ? Entity\Currency::FIAT : Entity\Currency::VIRTUAL
        );
        $fees = new PaymentFeeCollection($this->fees->getDepositFeesByBankId($bank->getId()));
        switch ($bank->getSlug()) {
            case 'international-wire-transfer':
                $deposit = new Model\InternationalWireDeposit($user, $limits, $fees);
                break;
            //case 'egopay':
            //case 'payza':
            //case 'okpay':
            //case 'perfect-money':
            //    $deposit = new Model\DepositModel($limits, $fees);
            //    break;
            //case 'astropay':
            //    $deposit = new Model\AstropayDeposit($limits, $fees);
            //    break;
            default:
                throw new UnknownBankException("Bank '{$bank->getSlug()}' is not available for deposits.");
        }

        return $deposit;
    }

    public function withdrawalForm(Entity\Bank $bank)
    {
        $user = $this->security->getToken()->getUser();
        switch ($bank->getSlug()) {
            case 'international-wire-transfer':
                $form = new Type\InternationalWireWithdrawalType($user, $this->countries->findAllChoosable());
                break;
            //case 'egopay':
            //case 'payza':
            //case 'okpay':
            //case 'perfect-money':
            //    $form = new Type\WithdrawalType($user);
            //    break;
            //case 'paypal':
            //    $form = new Type\PayPalWithdrawalType($user);
            //    break;
            //case 'moneygram':
            //    $form = new Type\MoneyGramWithdrawalType($user, $this->countries->findAllChoosable());
            //    break;
            //case 'westernunion':
            //    $form = new Type\WesternUnionWithdrawalType($user, $this->countries->findAllChoosable());
            //    break;
            case 'btc':
            case 'ltc':
            case 'eth':
            case 'bnk':
                $form = new Type\VirtualWithdrawalType($user);
                break;
            default:
                throw new UnknownBankException("Bank '{$bank->getSlug()}' is not available for withdrawals.");
        }
        return $form;
    }

    public function depositForm(Entity\Bank $bank)
    {
        $user = $this->security->getToken()->getUser();
        switch ($bank->getSlug()) {
            case 'international-wire-transfer':
                $form = new Type\InternationalWireDepositType($user);
                break;
            //case 'egopay':
            //case 'payza':
            //case 'okpay':
            //case 'perfect-money':
            //    $form = new Type\DepositType();
            //    break;
            //case 'astropay':
            //    $form = new Type\AstropayDepositType();
            //    break;
            default:
                throw new UnknownBankException("Bank '{$bank->getSlug()}' is not available for deposits.");
        }
        return $form;
    }

    public function depositEntity(Entity\Bank $bank, Model\DepositModel $model)
    {
        $user = $this->security->getToken()->getUser();
        switch ($bank->getPaymentMethod()) {
            case 'wire':
                $deposit = new Entity\WireDeposit();
                $deposit->setFirstname($model->getFirstname());
                $deposit->setLastname($model->getLastname());
                $deposit->setComment($model->getComment());
                break;
            case 'e-currency':
                $deposit = new Entity\Deposit();
                break;
            case 'virtual-currency':
                $deposit = new Entity\VirtualDeposit();
                break;
            case 'deposit-only':
                $deposit = new Entity\DepositOnly();
                break;
            default:
                throw new \RuntimeException('DepositFactory: unknown payment method . ' . $bank->getPaymentMethod());
        }

        foreach ($user->getWallets() as $wallet) {
            if ($wallet->getCurrency()->getCode() === $model->getCurrencyCode()) {
                $deposit->setWallet($wallet);
                break;
            }
        }
        $deposit->setAmount($model->getAmount());
        $deposit->setFeeAmount($model->getFeeApplied());
        $deposit->setBank($bank);

        return $deposit;
    }
}
