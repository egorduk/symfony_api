<?php

namespace spec\Btc\Component\Market\Service;

use Btc\Component\Market\Util\VoucherGeneratorInterface;
use Btc\Component\Market\Model\Voucher as VoucherModel;
use Btc\Component\Market\Service\Voucher;
use Btc\Component\Market\Service\Wallet;
use Btc\CoreBundle\Entity\Currency;
use Btc\CoreBundle\Entity\User;
use Btc\FrontendApiBundle\Events\AccountActivityEvents;
use Btc\FrontendApiBundle\Events\UserActivityEvent;
use Btc\FrontendApiBundle\Exception\Rest\NotEnoughMoneyException;
use Btc\FrontendApiBundle\Exception\Rest\NotValidDataException;
use Btc\FrontendApiBundle\Exception\Rest\UnknownErrorException;
use Btc\FrontendApiBundle\Form\CreateVoucherType;
use Btc\FrontendApiBundle\Form\RedeemVoucherType;
use Btc\FrontendApiBundle\Repository\VoucherRepository;
use Btc\FrontendApiBundle\Service\EmailSenderService;
use Btc\FrontendApiBundle\Service\RestService;
use Btc\CoreBundle\Entity\Voucher as VoucherEntity;
use Btc\CoreBundle\Entity\Wallet as WalletEntity;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\AbstractPlatform as Platform;
use Doctrine\ORM\EntityManager;
use Exmarkets\NsqBundle\Message\Voucher\CreateVoucherMessage;
use Exmarkets\NsqBundle\Message\Voucher\RedeemVoucherMessage;
use Exmarkets\NsqBundle\Nsq;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Symfony\Component\EventDispatcher\Debug\TraceableEventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;

class VoucherSpec extends ObjectBehavior
{
    function let(
        Connection $db,
        Nsq $nsq,
        VoucherGeneratorInterface $generator,
        Platform $platform,
        FormFactoryInterface $formFactory,
        EntityManager $em,
        EventDispatcherInterface $ed,
        EmailSenderService $emailSenderService,
        Request $request,
        ParameterBag $parameterBag,
        VoucherEntity $voucherEntity,
        VoucherRepository $voucherRepository
    ) {
        $db->getDatabasePlatform()->willReturn($platform);
        $platform->getDateTimeFormatString()->willReturn('Y-m-d H:i');
        $request->request = $parameterBag;
        $parameterBag->all()->willReturn([]);
        $em->getRepository($voucherEntity)->willReturn($voucherRepository);

        $this->beConstructedWith($db, $nsq, $generator, $formFactory, $em, $ed, $emailSenderService, $voucherEntity);
    }

    function it_is_initializable()
    {
        $this->shouldHaveType(Voucher::class);
        $this->shouldImplement(RestService::class);
    }

    function it_should_check_if_balance_in_wallet_is_sufficient_when_creating_voucher(
        VoucherModel $voucher,
        Connection $db,
        WalletEntity $wallet,
        Request $request
    ) {
        $db->isTransactionActive()->shouldBeCalled()->willReturn(false);
        $db->beginTransaction()->shouldBeCalled();

        $db->fetchAssoc(Wallet::SQL_LOCK, [1])
            ->shouldBeCalled()
            ->willReturn(['amount_available' => 100, 'amount_reserved' => 0]);

        $db->rollBack()->shouldBeCalled();

        $voucher->getIssuerWallet()->willReturn($wallet);
        $wallet->getId()->willReturn(1);
        $voucher->getAmount()->willReturn(200);

        $this->shouldThrow(NotEnoughMoneyException::class)
            ->duringCreate($voucher, $request);
    }

    function it_should_throw_exception_if_transaction_active(
        VoucherModel $voucher,
        Connection $db,
        Request $request
    )
    {
        $db->isTransactionActive()->shouldBeCalled()->willReturn(true);
        $this->shouldThrow(UnknownErrorException::class)
            ->duringCreate($voucher, $request);
    }

    function it_should_be_able_to_create_a_voucher(
        VoucherModel $voucher,
        Connection $db,
        Nsq $nsq,
        WalletEntity $wallet,
        VoucherGeneratorInterface $generator,
        Currency $currency,
        User $user,
        Request $request,
        TraceableEventDispatcher $ed,
        EmailSenderService $emailSenderService
    ) {
        $voucher->setCode(1234567890123456)->shouldBeCalled();
        $voucher->getCode()->willReturn(1234567890123456);
        $voucher->getStatus()->willReturn(VoucherModel::STATUS_OPEN);
        $voucher->getWallet()->willReturn($wallet);
        $wallet->getId()->willReturn(7);
        $voucher->getCurrency()->willReturn($currency);
        $currency->getId()->willReturn(3);
        $voucher->getIssuer()->willReturn($user);
        $user->getId()->willReturn(13);
        $voucher->setId(2)->shouldBeCalled();
        $voucher->setTimestamp(Argument::any())->shouldBeCalled();
        $voucher->getTimestamp()->willReturn(4656644);
        $voucher->getId()->willReturn(2);
        $voucher->getAmount()->willReturn(505);
        $voucher->getIssuerWallet()->willReturn($wallet);

        $db->isTransactionActive()->shouldBeCalled()->willReturn(false);
        $db->beginTransaction()->shouldBeCalled();
        $db->fetchAssoc(Wallet::SQL_LOCK, [7])
            ->shouldBeCalled()
            ->willReturn(['amount_available' => 505, 'amount_reserved' => 0]);
        $db->executeUpdate(Wallet::SQL_RESERVE, ['amount' => 505, 'wallet' => 7])->shouldBeCalled();

        $generator->generateCode()->willReturn(1234567890123456);

        $db->fetchArray(Voucher::CHECK_VOUCHER_CODE, Argument::any())->willReturn([]);
        $db->insert('vouchers', Argument::Any())->shouldBeCalled();
        $db->lastInsertId()->shouldBeCalled()->willReturn(2);

        $db->commit()->shouldBeCalled();

        $ed->dispatch(
            AccountActivityEvents::VOUCHER_ISSUE,
            Argument::type(UserActivityEvent::class)
        )->shouldBeCalled();

        $emailSenderService->sendVoucherIssueNotificationEmail(Argument::type(VoucherModel::class))->shouldBeCalled();

        $nsq->send(Argument::type(CreateVoucherMessage::class))->shouldBeCalled();

        $this->create($voucher, $request)->shouldReturn($voucher);
    }

    function it_should_be_able_to_redeem_a_voucher(
        VoucherModel $voucher,
        Connection $db,
        Nsq $nsq,
        WalletEntity $wallet,
        WalletEntity $redeemerWallet,
        Request $request,
        TraceableEventDispatcher $ed,
        EmailSenderService $emailSenderService,
        User $issuer,
        User $redeemer,
        Currency $currency
    ) {
        $voucher->getCode()->willReturn(1234567890123456);
        $voucher->setStatus(VoucherModel::STATUS_REDEEMED)->shouldBeCalled();
        $voucher->getStatus()->willReturn(VoucherModel::STATUS_REDEEMED);
        $voucher->getRedeemerWallet()->willReturn($wallet);
        $wallet->getId()->willReturn(7);
        $voucher->getCurrency()->willReturn($currency);
        $currency->getId()->willReturn(3);
        $voucher->getRedeemer()->willReturn($redeemer);
        $redeemer->getId()->willReturn(13);
        $voucher->setTimestamp(Argument::any())->shouldBeCalled();
        $voucher->getTimestamp()->willReturn(4656644);
        $voucher->getId()->willReturn(2);
        $voucher->getAmount()->willReturn(505);
        $voucher->getIssuerWallet()->willReturn($wallet);
        $voucher->getIssuer()->willReturn($issuer);
        $issuer->getId()->willReturn(10);

        $db->isTransactionActive()->shouldBeCalled()->willReturn(false);
        $db->beginTransaction()->shouldBeCalled();
        $db->fetchAssoc(Wallet::SQL_LOCK, [7])
            ->shouldBeCalled()
            ->willReturn(['amount_available' => 0, 'amount_reserved' => 505]);
        $db->executeUpdate(Wallet::SQL_REDUCE_RESERVE_AND_TOTAL, ['amount' => 505, 'wallet' => 7])->shouldBeCalled();

        $voucher->getRedeemerWallet()->willReturn($redeemerWallet);
        $redeemerWallet->getId()->willReturn(8);
        $db->fetchAssoc(Wallet::SQL_LOCK, [8])
            ->shouldBeCalled()
            ->willReturn(['amount_available' => 0, 'amount_reserved' => 0]);
        $db->executeUpdate(Wallet::SQL_CREDIT, ['amount' => 505, 'wallet' => 8])->shouldBeCalled();
        $db->update('vouchers', Argument::Any(), ['code' => 1234567890123456])->shouldBeCalled();
        $db->commit()->shouldBeCalled();

        $ed->dispatch(
            AccountActivityEvents::VOUCHER_REDEEM,
            Argument::type(UserActivityEvent::class)
        )->shouldBeCalled();

        $emailSenderService->sendVoucherRedeemNotificationEmail(Argument::type(VoucherModel::class))->shouldBeCalled();

        $nsq->send(Argument::type(RedeemVoucherMessage::class))->shouldBeCalled();

        $this->redeem($voucher, $request)->shouldReturn($voucher);
    }

    function it_should_process_redeem_form(
        VoucherEntity $voucherEntity,
        Request $request,
        FormFactoryInterface $formFactory,
        FormInterface $form,
        VoucherRepository $voucherRepository
    ) {
        $formFactory
            ->create(Argument::type(RedeemVoucherType::class))
            ->willReturn($form)
            ->shouldBeCalled();

        $form->submit([])->shouldBeCalled();
        $form->isValid()->willReturn(true);
        $form->getData()->willReturn($voucherEntity);
        $form->offsetGet('code')->willReturn($form);
        $form->get('code')->willReturn($form);

        $voucherRepository->findOneBy(Argument::any())->willReturn($voucherEntity);

        $this->processRedeemForm($request)->shouldReturn($voucherEntity);
    }

    function it_should_throw_exception_while_process_redeem_form(
        Request $request,
        FormFactoryInterface $formFactory,
        FormInterface $form
    ) {
        $formFactory
            ->create(Argument::type(RedeemVoucherType::class))
            ->willReturn($form)
            ->shouldBeCalled();

        $form->submit([])->shouldBeCalled();
        $form->isValid()->willReturn(false);

        $this->shouldThrow(NotValidDataException::class)
            ->duringProcessRedeemForm($request);
    }

    function it_should_throw_exception_while_process_create_form(
        Request $request,
        FormFactoryInterface $formFactory,
        FormInterface $form
    ) {
        $formFactory
            ->create(Argument::type(CreateVoucherType::class), Argument::type(VoucherModel::class))
            ->willReturn($form)
            ->shouldBeCalled();

        $form->submit([])->shouldBeCalled();
        $form->isValid()->willReturn(false);

        $this->shouldThrow(NotValidDataException::class)
            ->duringProcessCreateForm($request);
    }

    function it_should_process_create_form(
        VoucherEntity $voucherEntity,
        Request $request,
        FormFactoryInterface $formFactory,
        FormInterface $form
    ) {
        $formFactory
            ->create(Argument::type(CreateVoucherType::class), Argument::type(VoucherModel::class))
            ->willReturn($form)
            ->shouldBeCalled();

        $form->submit([])->shouldBeCalled();
        $form->isValid()->willReturn(true);
        $form->getData()->willReturn($voucherEntity);

        $this->processCreateForm($request)->shouldReturn($voucherEntity);
    }
}
