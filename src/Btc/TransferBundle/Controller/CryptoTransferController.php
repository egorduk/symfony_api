<?php

namespace Btc\TransferBundle\Controller;

use Btc\CoreBundle\Entity\Currency;
use Btc\CoreBundle\Entity\Bank;
use Btc\TradeBundle\Controller\TradeControllerExtension;
use Btc\FrontendBundle\Controller\FlashControllerExtension;
use Btc\UserBundle\Controller\HotpControllerExtension;
use Exmarkets\PaymentCoreBundle\Gateway\Coin\CoinApiInterface;
use Exmarkets\PaymentCoreBundle\Gateway\Coin\Exceptions\ApiException;
use Exmarkets\PaymentCoreBundle\Gateway\Service\WithdrawalPersister;
use Btc\UserBundle\Events\UserActivityEvent;
use Btc\UserBundle\Events\AccountActivityEvents;
use Btc\TransferBundle\Gateway\Coin\Exceptions\NewAddressLimitReachedException;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class CryptoTransferController extends Controller
{
    use TradeControllerExtension;
    use HotpControllerExtension;
    use FlashControllerExtension;

    /**
     * @Route("/deposit/c/{currency}", name="btc_transfer_virtual_deposit_crypto")
     * @Method({"GET"})
     * @ParamConverter("currency", options={"mapping": {"currency":"code"}})
     * @Template
     */
    public function depositAction(Currency $currency)
    {
        $addressRepository = $this->getDoctrine()->getRepository('BtcCoreBundle:DepositAddress');
        $addresses = $addressRepository->findUserAddresses($this->getUser(), $currency);


        $currencies = $this->get('currencies')->getVirtualCurrencies();
        try {
            $serviceCurrencyCode = $currency->isEth() ?  'eth' : $currency->getCode(); //all tokens use eth API
            $addressService = $this->get(sprintf('btc_transfer.service.coin.%s.address', strtolower($serviceCurrencyCode)));
            $address = $addressService->getAddress($this->getUser(), $currency);
        } catch (\Exception $e) {
            $this->get('logger')->error("{$currency->getCode()} deposit service failure: {$e->getMessage()}", ['exception' => $e]);
            return $this->render('BtcTransferBundle:CryptoTransfer:offline.html.twig');
        }

        return compact('address', 'currency', 'addresses', 'currencies');
    }

    /**
     * @Route("/deposit/c/{currency}/new", name="btc_transfer_virtual_address_new")
     * @Method({"GET"})
     * @ParamConverter("currency", options={"mapping": {"currency":"code"}})
     */
    public function newAddressAction(Currency $currency)
    {
        try {
            $serviceCurrencyCode = $currency->isEth() ?  'eth' : $currency->getCode(); //all tokens use eth API
            $addressService = $this->get(sprintf('btc_transfer.service.coin.%s.address', strtolower($serviceCurrencyCode)));
            $newAddress = $addressService->requestNewAddress($this->getUser(), $currency);

            $this->flashSuccess('new_address.success', ['%address%' => $newAddress]);
        } catch (NewAddressLimitReachedException $e) {
            $this->flashFailure('new_address.too_early');
        } catch (\Exception $e) {
            $this->get('logger')->error("{$currency->getCode()} new address service failure: {$e->getMessage()}", ['exception' => $e]);
            return $this->render('BtcTransferBundle:CryptoTransfer:offline.html.twig');
        }

        return $this->redirect(
            $this->generateUrl('btc_transfer_virtual_deposit_crypto', ['currency' => $currency->getCode()])
        );
    }

    /**
     * @Route("/withdrawal/c/{bank}", name="btc_transfer_virtual_withdrawal")
     * @Method({"GET", "POST"})
     * @ParamConverter(name="bank", options={"repository_method" = "findOneBySlugVirtual"})
     * @Template
     */
    public function withdrawAction(Request $request, Bank $bank)
    {
        $em = $this->getDoctrine()->getManager();
        $user = $this->getUser();
        $factory = $this->get('exmarkets_transfer.payment.factory');
        // will throw not found exception if bank does not support withdrawal
        $model = $factory->withdrawalModel($bank);
        $banks = $this->get('banks')->findBy(['fiat' => false]);
        $pending = $this->get('withdrawals')->getPendingWithdrawalsByUser($user);

        $form = $this->createForm($factory->withdrawalForm($bank), $model);
        // prepare a response builder function
        $response = function () use ($form, $bank, $banks, $pending, $model) {
            $form = $form->createView();
            return compact('form', 'bank', 'banks', 'model', 'pending');
        };
        $currency = $this->get('currencies')->findOneByCode($bank->getSlug());
        $model->setCurrency($currency);
        // maybe we need just to send two factor sms
        $form->handleRequest($request);
        if ($user->hasHOTP() && $form->get('sendSms')->isClicked()) {
            $this->sendHotp();
            return $response();
        }
        // continue with form validation
        if ($form->isValid()) {
            $wallet = $this->findWalletOr500($currency);
            $model->setWalletId($wallet->getId());
            $model->setFeeAmount($model->getFeeApplied());

            // checking if withdrawal address is someones deposit address
            $addresses = $em->getRepository('BtcCoreBundle:DepositAddress');
            if ($found = $addresses->findOneBy(['address' => $model->getForeignAccount()])) {
                $form->get("foreignAccount")->addError(new FormError($this->get('translator')->trans(
                    'withdrawal.error.internal_address', [], 'Withdrawal'
                )));
                return $response();
            }

            /** @var CoinApiInterface $addressService */
            $addressService = $this->get(sprintf('exm_payment_core.gateway.coin.%s.api', strtolower($currency->getCode())));
            try {
                $isValidAddress = $addressService->isAddressValid($model->getForeignAccount());
                if (!$isValidAddress) {
                    $form->get("foreignAccount")->addError(new FormError($this->get('translator')->trans(
                        'withdrawal.error.invalid_address', [], 'Withdrawal'
                    )));
                    return $response();
                }
                // simulates same logic as before, we have to persist with approving status
                $model->approving();
            } catch (ApiException $e) {
                $this->flashFailure('withdrawal.flash.general_coin_failure', [], 'Withdrawal');
                $this->get('logger')->critical('Withdrawal crypto: ' . $e->getMessage());
                return $response();
            }

            // attempt to persist withdrawal with wallet balance lock
            $persister = new WithdrawalPersister($this->get('db'), $this->get('nsq'));
            if ($error = $persister->requestWithdrawal($model)) {
                $form->get("amount")->addError(new FormError($error->message($this->get('translator'))));
                return $response();
            }

            $this->incHotpCounter();

            // notifications
            $this->get('event_dispatcher')->dispatch(
                AccountActivityEvents::WITHDRAWAL_REQUEST,
                new UserActivityEvent($user, $request)
            );

            $notifier = $this->get('btc_user.notifications_service');
            $notifier->notifyAboutWithdrawal($model, $bank);

            $this->flashSuccess('withdrawal.flash.success_amount', [
                '%amount%' => $this->get('currency')->priceFilter($model->getAmount(), $model->getCurrencyCode()),
            ], 'Deposit');

            return $this->redirect($this->generateUrl('btc_account_withdrawals'));
        }
        return $response();
    }
}
