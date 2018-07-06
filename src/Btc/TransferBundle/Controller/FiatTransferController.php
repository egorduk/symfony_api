<?php

namespace Btc\TransferBundle\Controller;

use Btc\CoreBundle\Entity\Bank;
use Btc\CoreBundle\Entity\Withdrawal;
use Btc\TradeBundle\Controller\TradeControllerExtension;
use Btc\UserBundle\Controller\HotpControllerExtension;
use Btc\FrontendBundle\Controller\FlashControllerExtension;
use Btc\UserBundle\Events\UserActivityEvent;
use Btc\UserBundle\Events\AccountActivityEvents;
use Exmarkets\PaymentCoreBundle\Gateway\Service\WithdrawalPersister;

use Symfony\Component\Form\FormError;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;

class FiatTransferController extends Controller
{
    use TradeControllerExtension;
    use HotpControllerExtension;
    use FlashControllerExtension;
    use TransferAccessControllerExtension;

    /**
     * @Route("/deposit/with/{bank}", name="btc_transfer_deposit_bank")
     * @Method({"GET", "POST"})
     * @ParamConverter(name="bank", options={"repository_method" = "findOneBySlugFiat"})
     * @Template
     */
    public function depositAction(Request $request, Bank $bank)
    {
        if ($redirect = $this->redirectResponseIfNoTransferAccess($bank)) {
            return $redirect;
        }

        switch ($bank->getSlug()) {
            case "egopay": // egopay is currently disabled
                return $this->render('BtcTransferBundle::disabled.html.twig', compact('bank'));
        }

        $factory = $this->get('exmarkets_transfer.payment.factory');
        // will throw exception if bank does not support deposit
        $model = $factory->depositModel($bank);
        $banks = $this->get('rest.repository.bank')->getAvailableFiatBanksToDeposit();
        $em = $this->getDoctrine()->getManager();

        $form = $this->createForm($factory->depositForm($bank), $model);
        $form->handleRequest($request);
        if ($form->isValid()) {
            $deposit = $factory->depositEntity($bank, $model);

            $em->persist($deposit);
            $em->flush();

            $this->get('event_dispatcher')->dispatch(
                AccountActivityEvents::DEPOSIT_REQUEST,
                new UserActivityEvent($this->getUser(), $request)
            );

            $notifier = $this->get('rest.service.notifications');
            $notifier->notifyAboutDeposit($model, $bank);

            return $this
                ->get('exm_payment_core.gateway.checkout')
                ->make($bank->getSlug())
                ->checkout($deposit, $model->getCustomParams());
        }

        $form = $form->createView();
        $fees = $em->getRepository('BtcCoreBundle:PaymentFee')->findBy(['name' => 'Deposit']);
        return compact('form', 'bank', 'banks', 'model', 'fees');
    }

    /**
     * @Route("/withdrawal/to/{bank}", name="btc_transfer_withdrawal_to")
     * @Method({"GET", "POST"})
     * @ParamConverter(name="bank", options={"repository_method" = "findOneBySlugFiat"})
     * @Template
     */
    public function withdrawAction(Request $request, Bank $bank)
    {
        if ($redirect = $this->redirectResponseIfNoTransferAccess($bank)) {
            return $redirect;
        }

        $em = $this->getDoctrine()->getManager();
        $factory = $this->get('exmarkets_transfer.payment.factory');
        // will throw not found exception if bank does not support withdrawal
        $model = $factory->withdrawalModel($bank, $request);
        $banks = $this->get('banks')->getAvailableFiatBanksToWithdraw();
        $pending = $this->get('withdrawals')->getPendingWithdrawalsByUser($this->getUser());
        $fees = $em->getRepository('BtcCoreBundle:PaymentFee')->findBy(['name' => 'Withdrawal']);

        $form = $this->createForm($factory->withdrawalForm($bank), $model);
        // prepare a response builder function
        $response = function() use($form, $bank, $banks, $pending, $fees, $model) {
            $form = $form->createView();
            return compact('form', 'bank', 'banks', 'model', 'fees', 'pending');
        };
        // maybe we need just to send two factor sms
        $form->handleRequest($request);
        $user = $this->getUser();

        if ($user->hasHOTP() && $form->get('sendSms')->isClicked()) {
            $this->sendHotp();
            return $response();
        }
        // continue with form validation
        if ($form->isValid()) {
            $wallet = $this->findWalletOr500($model->getCurrency());
            $model->setWalletId($wallet->getId());
            $model->setFeeAmount($model->getFeeApplied());

            $persister = new WithdrawalPersister($this->get('db'), $this->get('nsq'));
            if ($error = $persister->requestWithdrawal($model)) {
                $form->get("amount")->addError(new FormError($error->message($this->get('translator'))));
                return $response();
            }
            $this->get('event_dispatcher')->dispatch(
                AccountActivityEvents::WITHDRAWAL_REQUEST,
                new UserActivityEvent($this->getUser(), $request)
            );
            /*
             * @TODO Discuss this.
             *       HOTP Counter increment logic could be done through events.
             *       Furthermore, the notification logic could benefit from usage of events
             */
            $this->incHotpCounter();

            $notifier = $this->get('btc_user.notifications_service');
            $notifier->notifyAboutWithdrawal($model, $bank);

            $this->flashSuccess('withdrawal.flash.success_amount', [
                '%amount%' => $this->get('currency')->priceFilter($model->getAmount(), $model->getCurrencyCode()),
            ], 'Deposit');

            return $this->redirect($this->generateUrl('btc_account_withdrawals'));
        }
        return $response();
    }

    /**
     * @Route("/withdrawal/cancel/{withdrawal}", name="btc_transfer_withdrawal_cancel")
     * @Method({"GET", "POST"})
     * @ParamConverter(name="withdrawal")
     */
    public function cancelPendingWithdrawal(Withdrawal $withdrawal)
    {
        $bank = $withdrawal->getBank();
        if ($withdrawal->getUser() === $this->getUser() && ($withdrawal->isNew() || $withdrawal->isApproving())) {
            $cancellationService = $this->get('exm_payment_core.gateway.service.cancellation');
            $cancellationService->cancel($withdrawal);
            $this->flashSuccess('withdrawal.cancel.completed', ['%bank%' => $bank->getName()], 'Withdrawal');
        } else {
            $this->flashFailure('withdrawal.cancel.failed', ['%bank%' => $bank->getName()], 'Withdrawal');
        }
        $route = $bank->getFiat() ? 'btc_transfer_withdrawal_to' : 'btc_transfer_virtual_withdrawal';
        return $this->redirect($this->generateUrl($route, ['bank' => $bank->getSlug()]));
    }
}
