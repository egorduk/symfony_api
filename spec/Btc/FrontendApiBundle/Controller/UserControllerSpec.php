<?php

namespace spec\Btc\FrontendApiBundle\Controller;

use Btc\Component\Market\Model\FeeSet;
use Btc\Component\Market\Service\FeeService;
use Btc\CoreBundle\Entity\Bank;
use Btc\CoreBundle\Entity\Currency;
use Btc\CoreBundle\Entity\PhoneVerification;
use Btc\CoreBundle\Entity\User;
use Btc\CoreBundle\Entity\Verification;
use Btc\CoreBundle\Entity\Wallet;
use Btc\CoreBundle\Service\UserRegistrationService;
use Btc\FrontendApiBundle\Classes\RestSecurity;
use Btc\FrontendApiBundle\Controller\UserController;
use Btc\FrontendApiBundle\Events\AccountActivityEvents;
use Btc\FrontendApiBundle\Events\UserActivityEvent;
use Btc\FrontendApiBundle\Exception\Rest\AlreadyExistsException;
use Btc\FrontendApiBundle\Exception\Rest\InvalidCredentialsException;
use Btc\FrontendApiBundle\Exception\Rest\NotFoundException;
use Btc\FrontendApiBundle\Exception\Rest\NotValidDataException;
use Btc\FrontendApiBundle\Exception\Rest\TwoFactorAuthDisabledException;
use Btc\FrontendApiBundle\Exception\Rest\UserNotFoundException;
use Btc\FrontendApiBundle\Repository\ActivityRepository;
use Btc\FrontendApiBundle\Repository\BankRepository;
use Btc\FrontendApiBundle\Repository\CoinAddressRepository;
use Btc\FrontendApiBundle\Repository\CurrencyRepository;
use Btc\FrontendApiBundle\Repository\PhoneRepository;
use Btc\FrontendApiBundle\Repository\VerificationRepository;
use Btc\FrontendApiBundle\Repository\WalletRepository;
use Btc\FrontendApiBundle\Service\ActivityLoggerService;
use Btc\FrontendApiBundle\Service\AuthService;
use Btc\FrontendApiBundle\Service\CurrencyService;
use Btc\FrontendApiBundle\Service\EmailSenderService;
use Btc\FrontendApiBundle\Service\NotificationsService;
use Btc\FrontendApiBundle\Service\PhoneService;
use Btc\FrontendApiBundle\Service\PinService;
use Btc\FrontendApiBundle\Service\QrCodeService;
use Btc\FrontendApiBundle\Service\RestRedis;
use Btc\FrontendApiBundle\Service\UserFeeSetService;
use Btc\FrontendApiBundle\Service\UserService;
use Btc\FrontendApiBundle\Service\WalletService;
use Btc\PaginationBundle\Paginator;
use Btc\TransferBundle\Factory\PaymentFactory;
use Btc\TransferBundle\Form\Type\VirtualWithdrawalType;
use Btc\TransferBundle\Service\Coin\AddressService;
use Doctrine\ORM\EntityManager;
use Exmarkets\PaymentCoreBundle\Gateway\Coin\Api;
use Exmarkets\PaymentCoreBundle\Gateway\Model\VirtualWithdrawal;
use Exmarkets\PaymentCoreBundle\Gateway\Model\WithdrawModel;
use Exmarkets\PaymentCoreBundle\Gateway\Service\WithdrawalPersister;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Request\ParamFetcher;
use FOS\RestBundle\View\View;
use FOS\RestBundle\View\ViewHandler;
use JMS\Serializer\Context;
use JMS\Serializer\Serializer;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\Debug\TraceableEventDispatcher;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormFactory;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\PreAuthenticatedToken;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\EmailValidator;
use Symfony\Component\Validator\ValidatorBuilderInterface;

class UserControllerSpec extends ObjectBehavior
{
    const EMAIL_FAKE = 'email@test.com';
    const PIN_RAW_FAKE = '1234';
    const PIN_ENCODED_FAKE = 'a1b2c3d4';
    const PIN_LENGTH = 7;
    const SALT_FAKE = 'salt';
    const USER_ID_FAKE = 1;
    const WALLET_ID_FAKE = 1;
    const TOKEN_FAKE = 'token';
    const CURRENCY_CODE_FAKE = 'btc';
    const BANK_SLUG_FAKE = 'bank_slug';
    const AMOUNT_FAKE = 123;
    const ADDRESS_FAKE = 'address';
    const PASSWORD_RAW_FAKE = 'password';
    const DAYS_FAKE = 100;
    const STATUS_FAKE = 'new';
    const CURRENCY_ID_FAKE = 1;
    const PHONE_FAKE = '123456';
    const ACTION_FAKE = 'new_action';
    const USER_NAME_FAKE = 'username';
    const AUTH_KEY_FAKE = 'auth_key';
    const URL_FAKE = 'url';

    public function let(
        ContainerInterface $container,
        ViewHandler $viewHandler,
        Request $request,
        UserService $userService,
        PinService $pinService,
        TokenStorage $tokenStorage,
        User $user,
        PreAuthenticatedToken $preAuthenticatedToken,
        EmailSenderService $emailSenderService,
        PhoneService $phoneService,
        EntityManager $em,
        Response $response,
        VerificationRepository $verificationRepository,
        ValidatorBuilderInterface $validatorBuilder,
        EmailValidator $emailValidator,
        ParamFetcher $paramFetcher,
        AuthService $authService,
        FeeService $feeService,
        Serializer $serializer,
        WalletService $walletService,
        CurrencyService $currencyService,
        BankRepository $bankRepository,
        PaymentFactory $paymentFactory,
        CurrencyRepository $currencyRepository,
        FormFactory $formFactory,
        WalletRepository $walletRepository,
        CoinAddressRepository $coinAddressRepository,
        Currency $currency,
        Api $api,
        WithdrawalPersister $withdrawalPersister,
        NotificationsService $notificationsService,
        TraceableEventDispatcher $eventDispatcher,
        UserRegistrationService $userRegistrationService,
        ActivityLoggerService $activityLoggerService,
        Paginator $paginator,
        AddressService $addressService,
        PhoneRepository $phoneRepository,
        ActivityRepository $activityRepository,
        QrCodeService $qrCodeService,
        UserFeeSetService $userFeeSetService
    ) {
        $this->setContainer($container);

        $container->get('rest.service.user')->willReturn($userService);
        $container->get('rest.service.pin')->willReturn($pinService);
        $container->get('rest.service.mailer')->willReturn($emailSenderService);
        $container->get('rest.service.phone')->willReturn($phoneService);
        $container->get('validator.builder')->willReturn($validatorBuilder);
        $container->get('em')->willReturn($em);
        $container->get('paginator')->willReturn($paginator);
        $container->get('rest.repository.verification')->willReturn($verificationRepository);
        $container->get('security.token_storage')->willReturn($tokenStorage);
        $container->has('security.token_storage')->willReturn(true);
        $container->get('rest.service.auth')->willReturn($authService);
        $container->get('rest.service.fee_service')->willReturn($feeService);
        $container->get('rest.service.wallet')->willReturn($walletService);
        $container->get('rest.service.currency')->willReturn($currencyService);
        $container->get('rest.service.notifications')->willReturn($notificationsService);
        $container->get('rest.service.activity_logger')->willReturn($activityLoggerService);
        $container->get('core.user_registration_service')->willReturn($userRegistrationService);
        $container->get('rest.repository.bank')->willReturn($bankRepository);
        $container->get('rest.repository.currency')->willReturn($currencyRepository);
        $container->get('rest.repository.wallet')->willReturn($walletRepository);
        $container->get('rest.repository.deposit_address')->willReturn($coinAddressRepository);
        $container->get('rest.repository.phone_verification')->willReturn($phoneRepository);
        $container->get('rest.repository.activity')->willReturn($activityRepository);
        $container->get('exm_payment_core.gateway.coin.btc.api')->willReturn($api);
        $container->get('rest.service.coin.eth.address')->willReturn($addressService);
        $container->get('rest.service.coin.btc.address')->willReturn($addressService);
        $container->get('rest.service.withdrawal_persister')->willReturn($withdrawalPersister);
        $container->get('rest.service.qr_code')->willReturn($qrCodeService);
        $container->get('rest.service.user_fee_set')->willReturn($userFeeSetService);
        $container->get('form.factory')->willReturn($formFactory);
        $container->get('exmarkets_transfer.payment.factory')->willReturn($paymentFactory);
        $container->get('event_dispatcher')->willReturn($eventDispatcher);
        $container->get('jms_serializer')->willReturn($serializer);
        $container->get('rest.redis')->willReturn(new RestRedis('127.0.0.1', '6379', 1));   // mock redis without segmentation fault
        $container->get('fos_rest.view_handler')->willReturn($viewHandler);

        $request->get('email')->willReturn(self::EMAIL_FAKE);
        $request->get('bankSlug')->willReturn(self::BANK_SLUG_FAKE);

        $paramFetcher->get('email')->willReturn(self::EMAIL_FAKE);
        $paramFetcher->get('pin')->willReturn(self::PIN_RAW_FAKE);
        $paramFetcher->get('amount')->willReturn(self::AMOUNT_FAKE);
        $paramFetcher->get('currencyCode')->willReturn(self::CURRENCY_CODE_FAKE);
        $paramFetcher->get('newPassword')->willReturn(self::PASSWORD_RAW_FAKE);
        $paramFetcher->get('days')->willReturn(self::DAYS_FAKE);
        $paramFetcher->get('status')->willReturn(self::STATUS_FAKE);
        $paramFetcher->get('pageNum')->willReturn(1);
        $paramFetcher->get('limit')->willReturn(10);
        $paramFetcher->get('currencyId')->willReturn(self::CURRENCY_ID_FAKE);
        $paramFetcher->get('action')->willReturn(self::ACTION_FAKE);

        $currency->getCode()->willReturn(self::CURRENCY_CODE_FAKE);

        $validatorBuilder->getValidator()->willReturn($emailValidator);

        $tokenStorage->getToken()->willReturn($preAuthenticatedToken);
        $preAuthenticatedToken->getUser()->willReturn($user);

        $viewHandler->handle(Argument::type(View::class))->willReturn($response);

        $user->getPin()->willReturn(self::PIN_RAW_FAKE);
        $user->getSalt()->willReturn(self::SALT_FAKE);
        $user->getId()->willReturn(self::USER_ID_FAKE);
        $user->hasHOTP()->willReturn(true);
        $user->getHotpAuthCounter()->willReturn(0);
        $user->getUsername()->willReturn(self::USER_NAME_FAKE);
        $user->getAuthKey()->willReturn(self::AUTH_KEY_FAKE);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(UserController::class);
        $this->shouldHaveType(FOSRestController::class);
    }

    public function it_should_respond_to_register_user_action(
        Request $request,
        User $user,
        UserService $userService,
        PinService $pinService,
        EmailSenderService $emailSenderService
    ) {
        $userService->getOneBy(['email' => self::EMAIL_FAKE])->willReturn(null);
        $userService->processRegisterForm(Argument::type(Request::class))->willReturn($user);

        $pinService->generate(Argument::exact(self::PIN_LENGTH))->willReturn(self::PIN_RAW_FAKE);
        $pinService->encodePin(Argument::any(), Argument::any())->willReturn(self::PIN_ENCODED_FAKE);

        $user->getSalt()->willReturn('salt')->shouldBeCalled();
        $user->setPin(self::PIN_RAW_FAKE)->shouldBeCalled();
        $user->setPin(self::PIN_ENCODED_FAKE)->shouldBeCalled();

        $userService->patch(Argument::type(User::class))->willReturn($user)->shouldBeCalled();

        $emailSenderService->sendNewPinMessage(Argument::type(User::class))->shouldBeCalled();

        $response = $this->registerUserAction($request);
        $response->shouldHaveType(Response::class);
    }

    public function it_should_respond_to_send_hotp_action(PhoneService $phoneService, EntityManager $em, User $user)
    {
        $user->hasHOTP()->willReturn(true);

        $phoneService->sendHotp(Argument::type(User::class))->willReturn(null);
        $em->flush()->willReturn(null);

        $response = $this->sendHotpAction();
        $response->shouldHaveType(Response::class);
    }

    public function it_should_respond_to_logout_action(User $user, UserService $userService)
    {
        $user->setToken(Argument::exact(''))->shouldBeCalled();

        $userService->patch(Argument::type(User::class))->willReturn($user)->shouldBeCalled();

        $response = $this->logoutAction();
        $response->shouldHaveType(Response::class);
    }

    public function it_should_respond_to_update_action(
        User $user,
        UserService $userService,
        Request $request,
        Verification $verification,
        View $view
    ) {
        $user->getVerification()->willReturn($verification);

        $userService
            ->processUpdateUserProfileForm(Argument::type(Request::class), Argument::type(Verification::class))
            ->willReturn($user);

        $userService->getUserView(Argument::type(User::class))->willReturn($view);

        $response = $this->updateAction($request);
        $response->shouldHaveType(Response::class);
    }

    public function it_should_respond_to_get_user_info_action(UserService $userService, View $view)
    {
        $userService->getUserView(Argument::type(User::class))->willReturn($view);

        $response = $this->getUserInfoAction();
        $response->shouldHaveType(Response::class);
    }

    public function it_should_respond_to_post_user_prelogin_by_email_action(
        UserService $userService,
        ParamFetcher $paramFetcher,
        EmailValidator $emailValidator,
        \Countable $countable,
        User $user,
        EmailSenderService $emailSenderService,
        PinService $pinService
    ) {
        $emailValidator->validate(Argument::any(), Argument::type(Constraint::class))->willReturn($countable)->shouldBeCalled();
        $countable->count()->willReturn(0)->shouldBeCalled();

        $userService->getOneBy(['email' => self::EMAIL_FAKE])->willReturn($user);

        $user->hasRole("ROLE_BLOCKED")->willReturn(false);

        $user->getId()->willReturn(1)->shouldBeCalled();
        $user->getSalt()->willReturn('salt')->shouldBeCalled();
        $user->setPin(self::PIN_RAW_FAKE)->shouldBeCalled();
        $user->setPin(self::PIN_ENCODED_FAKE)->shouldBeCalled();

        $pinService->generate(Argument::exact(self::PIN_LENGTH))->willReturn(self::PIN_RAW_FAKE);
        $pinService->encodePin(Argument::any(), Argument::any())->willReturn(self::PIN_ENCODED_FAKE);

        $key = sprintf(RestSecurity::CNT_REQUEST_GENERATE_PIN_KEY, 1);
        $redis = new RestRedis('127.0.0.1', '6379', 1);
        $redis->set($key, 4);

        $userService->patch(Argument::type(User::class))->willReturn($user)->shouldBeCalled();

        $emailSenderService->sendNewPinMessage(Argument::type(User::class))->shouldBeCalled();

        $response = $this->postUserPreLoginByEmailAction($paramFetcher);
        $response->shouldHaveType(Response::class);
    }

    public function it_should_respond_to_post_user_login_by_email_and_pin_action(
        UserService $userService,
        ParamFetcher $paramFetcher,
        User $user,
        PinService $pinService,
        Serializer $serializer,
        FeeService $feeService,
        FeeSet $feeSet,
        Request $request,
        TraceableEventDispatcher $eventDispatcher
    ) {
        $userService->getOneBy(['email' => self::EMAIL_FAKE])->willReturn($user)->shouldBeCalled();

        $pinService->isPinValid(Argument::any(), self::PIN_RAW_FAKE, Argument::any())->willReturn(true)->shouldBeCalled();

        $user->hasRole(User::BLOCKED)->willReturn(false);
        $user->setToken(null)->shouldBeCalled();

        $userService->patch(Argument::type(User::class))->willReturn($user)->shouldBeCalled();

        $feeService->getFeeSetPercentByUser(Argument::type(User::class))->willReturn($feeSet)->shouldBeCalled();

        $feeSet->getFeeName()->willReturn('name');
        $feeSet->getFeeType()->willReturn('type');
        $feeSet->getMarketFeePercents()->willReturn([]);

        $serializer->serialize(Argument::type(User::class), Argument::exact('json'), Argument::type(Context::class))->willReturn($user)->shouldBeCalled();
        $serializer->deserialize(Argument::type(User::class), User::class, Argument::exact('json'))->willReturn($user)->shouldBeCalled();

        $user->setFeeSet(['name' => 'name', 'type' => 'type', 'fees' => []])->shouldBeCalled();

        $eventDispatcher->dispatch(
            AccountActivityEvents::CUSTOM_LOGIN,
            Argument::type(UserActivityEvent::class)
        )->shouldBeCalled();

        $response = $this->postUserLoginByEmailPinAction($paramFetcher, $request);
        $response->shouldHaveType(Response::class);
    }

    public function it_should_respond_to_get_user_by_id_action(UserService $userService, User $user, View $view)
    {
        $userService->get(self::USER_ID_FAKE)->willReturn($user)->shouldBeCalled();

        $userService->getUserView(Argument::type(User::class))->willReturn($view);

        $response = $this->getUserByUserIdAction(self::USER_ID_FAKE);
        $response->shouldHaveType(Response::class);
    }

    public function it_should_respond_to_get_user_wallet_balance_action(User $user)
    {
        $user->getWallets()->willReturn([])->shouldBeCalled();

        $response = $this->getUserWalletBalanceAction();
        $response->shouldHaveType(Response::class);
    }

    /* TODO: fix later */
    public function it_should_respond_to_withdraw_action(
        Request $request,
        Bank $bank,
        BankRepository $bankRepository,
        PaymentFactory $paymentFactory,
        VirtualWithdrawal $virtualWithdrawal,
        VirtualWithdrawalType $virtualWithdrawalType,
        FormFactory $formFactory,
        Form $form,
        WalletRepository $walletRepository,
        Wallet $wallet,
        CurrencyRepository $currencyRepository,
        Currency $currency,
        Api $api,
        User $user,
        NotificationsService $notificationsService,
        ActivityLoggerService $activityLoggerService,
        CoinAddressRepository $coinAddressRepository
    ) {
        $bankRepository->findOneBy(['slug' => self::BANK_SLUG_FAKE])->willReturn($bank)->shouldBeCalled();
        $currencyRepository->findOneBy(['code' => self::BANK_SLUG_FAKE])->willReturn($currency)->shouldBeCalled();

        $bank->getSlug()->willReturn(self::BANK_SLUG_FAKE);

        $paymentFactory->withdrawalModel(Argument::type(Bank::class))->willReturn($virtualWithdrawal)->shouldBeCalled();
        $paymentFactory->withdrawalForm(Argument::type(Bank::class))->willReturn($virtualWithdrawalType)->shouldBeCalled();

        $virtualWithdrawal->setCurrency(Argument::type(Currency::class))->shouldBeCalled();

        $wallet->getId()->willReturn(self::WALLET_ID_FAKE);

        $virtualWithdrawal->setWalletId(self::WALLET_ID_FAKE)->shouldBeCalled();
        $virtualWithdrawal->getFeeApplied()->willReturn(self::AMOUNT_FAKE)->shouldBeCalled();
        $virtualWithdrawal->setFeeAmount(self::AMOUNT_FAKE)->shouldBeCalled();

        $formFactory->create($virtualWithdrawalType, $virtualWithdrawal)->willReturn($form)->shouldBeCalled();

        $form->submit($request)->willReturn(true);
        $form->isValid()->willReturn(true);

        $walletRepository->findOneForUserAndCurrency(Argument::type(User::class), Argument::type(Currency::class))->willReturn($wallet)->shouldBeCalled();

        $virtualWithdrawal->getForeignAccount()->willReturn(self::ADDRESS_FAKE);
        $virtualWithdrawal->approving()->shouldBeCalled();

        $user->setHotpAuthCounter(Argument::exact(1))->shouldBeCalled();
        $user->setHotpSentTimes(Argument::exact(0))->shouldBeCalled();

        $coinAddressRepository->findOneBy(['address' => self::ADDRESS_FAKE])->willReturn(false)->shouldBeCalled();

        $api->isAddressValid(self::ADDRESS_FAKE)->willReturn(true)->shouldBeCalled();

        $activityLoggerService->logUserWithdraw(Argument::type(User::class), Argument::type(Request::class))->shouldBeCalled();

        $notificationsService->notifyAboutWithdraw(Argument::type(WithdrawModel::class), Argument::type(Bank::class))->shouldBeCalled();

        $response = $this->withdrawAction($request);
        $response->shouldHaveType(Response::class);
    }

    /*TODO: issue with clone $qb*/
    /*function it_should_respond_to_get_deposits_histories_action(
        ParamFetcher $paramFetcher,
        CurrencyService $currencyService,
        Currency $currency,
        DepositRepository $depositRepository,
        QueryBuilder $qb,
        Paginator $paginator,
        SlidingPagination $slidingPagination
    ) {
        $currencyService->getOneBy(['code' => self::CURRENCY_CODE_FAKE])->willReturn($currency)->shouldBeCalled();

        *$depositRepository
            ->getUserDepositsQueryBuilder(Argument::type(User::class), Argument::any(), Argument::any(), Argument::type(Currency::class))
            ->willReturn($qb)
            ->shouldBeCalled();

        new Target($qb->getWrappedObject());

        $paginator->paginate(Argument::type(Request::class), Argument::type(Target::class))->willReturn($slidingPagination)->shouldBeCalled();

        $response = $this->getDepositsHistoriesAction($paramFetcher);
        $response->shouldHaveType(Response::class);
    }*/

    public function it_should_respond_to_change_user_password_action(
        User $user,
        ParamFetcher $paramFetcher,
        ActivityLoggerService $activityLoggerService,
        Request $request,
        UserService $userService,
        UserRegistrationService $userRegistrationService
    ) {
        $user->setPlainPassword(self::PASSWORD_RAW_FAKE)->shouldBeCalled();
        $user->removeRole(Argument::any())->shouldBeCalled();

        $userRegistrationService->encryptPassword(Argument::type(User::class))->shouldBeCalled();

        $userService->patch(Argument::type(User::class))->willReturn($user)->shouldBeCalled();

        $activityLoggerService->logUserChangePassword(Argument::type(User::class), Argument::type(Request::class))->shouldBeCalled();

        $response = $this->changeUserPasswordAction($paramFetcher, $request);
        $response->shouldHaveType(Response::class);
    }

    public function it_should_respond_to_generate_user_crypto_address_action(
        ParamFetcher $paramFetcher,
        CurrencyService $currencyService,
        Currency $currency,
        AddressService $addressService
    ) {
        $currencyService->get(self::CURRENCY_ID_FAKE)->willReturn($currency)->shouldBeCalled();

        $currency->isEth()->willReturn(false)->shouldBeCalled();

        $addressService->requestNewAddress(Argument::type(User::class), Argument::type(Currency::class))
            ->willReturn(self::ADDRESS_FAKE)
            ->shouldBeCalled();

        $response = $this->generateUserCryptoAddressAction($paramFetcher);
        $response->shouldHaveType(Response::class);
    }

    public function it_should_respond_to_get_user_crypto_address_action(
        CurrencyService $currencyService,
        Currency $currency,
        CoinAddressRepository $coinAddressRepository,
        AddressService $addressService,
        CurrencyRepository $currencyRepository
    ) {
        $currencyService->get(self::CURRENCY_ID_FAKE)->willReturn($currency)->shouldBeCalled();

        $currencyRepository->getVirtualCurrencies()->willReturn([])->shouldBeCalled();

        $coinAddressRepository
            ->findUserAddresses(Argument::type(User::class), Argument::type(Currency::class))
            ->willReturn(self::ADDRESS_FAKE)
            ->shouldBeCalled();

        $currency->isEth()->willReturn(false)->shouldBeCalled();

        $addressService->getAddress(Argument::type(User::class), Argument::type(Currency::class))
            ->willReturn(self::ADDRESS_FAKE)
            ->shouldBeCalled();

        $response = $this->getUserCryptoAddressAction(self::CURRENCY_ID_FAKE);
        $response->shouldHaveType(Response::class);
    }

    public function it_should_respond_to_send_pin_phone_action(
        PhoneRepository $phoneRepository,
        PhoneVerification $phoneVerification,
        Request $request,
        UserService $userService
    ) {
        $phoneRepository
            ->findNotConfirmedByUser(Argument::type(User::class))
            ->willReturn($phoneVerification)
            ->shouldBeCalled();

        $phoneVerification->getPhone()->willReturn(self::PHONE_FAKE);

        $userService
            ->processPhoneVerificationForm(Argument::type(Request::class), Argument::type(PhoneVerification::class))
            ->willReturn($phoneVerification)
            ->shouldBeCalled();

        $response = $this->sendPinPhoneAction($request);
        $response->shouldHaveType(Response::class);
    }

    public function it_should_respond_verify_phone_pin_code_action(
        PhoneVerification $phoneVerification,
        Request $request,
        UserService $userService
    ) {
        $userService
            ->processPhonePinVerificationForm(Argument::type(Request::class), Argument::type(User::class))
            ->willReturn($phoneVerification)
            ->shouldBeCalled();

        $response = $this->verifyPhonePinCodeAction($request);
        $response->shouldHaveType(Response::class);
    }

    /*TODO: issue with clone $qb*/
    /*function it_should_respond_get_user_activities_action(
        Request $request,
        UserService $userService,
        ParamFetcher $paramFetcher
    ) {
        $response = $this->getUserActivitiesAction($paramFetcher);
        $response->shouldHaveType(Response::class);
    }*/

    public function it_should_respond_change_user_preferences_action(
        Request $request,
        UserService $userService,
        User $user
    ) {
        $userService
            ->processChangeUserPreferencesForm(Argument::type(Request::class), Argument::type(User::class))
            ->willReturn($user)
            ->shouldBeCalled();

        $response = $this->changeUserPreferencesAction($request);
        $response->shouldHaveType(Response::class);
    }

    public function it_should_respond_turn_on_google_auth_action(
        Request $request,
        UserService $userService,
        User $user
    ) {
        $userService
            ->processGoogleAuthForm(Argument::type(Request::class), Argument::type(User::class), Argument::any())
            ->willReturn($user)
            ->shouldBeCalled();

        $response = $this->turnOnGoogleAuthorizationAppAction($request);
        $response->shouldHaveType(Response::class);
    }

    public function it_should_respond_turn_off_google_auth_action(
        Request $request,
        UserService $userService,
        User $user
    ) {
        $userService
            ->processGoogleAuthForm(Argument::type(Request::class), Argument::type(User::class), Argument::any())
            ->willReturn($user)
            ->shouldBeCalled();

        $response = $this->turnOffGoogleAuthorizationAppAction($request);
        $response->shouldHaveType(Response::class);
    }

    public function it_should_respond_get_google_auth_action(
        UserService $userService,
        User $user,
        QrCodeService $qrCodeService
    ) {
        $userService->setNewAuthKey(Argument::type(User::class))->willReturn($user)->shouldBeCalled();

        $qrCodeService->getUrl(Argument::any(), Argument::any())->willReturn(self::URL_FAKE)->shouldBeCalled();

        $response = $this->getGoogleAuthorizationAppAction();
        $response->shouldHaveType(Response::class);
    }

    public function it_should_respond_get_user_stats_action(
        UserFeeSetService $userFeeSetService,
        \Btc\CoreBundle\Entity\FeeSet $feeSet,
        User $user
    ) {
        $userFeeSetService->getOneBy(['user' => $user])->willReturn($feeSet)->shouldBeCalled();

        $response = $this->getUsersStatsAction();
        $response->shouldHaveType(Response::class);
    }

    public function it_throws_an_exception_if_not_valid_email(ParamFetcher $paramFetcher, EmailValidator $emailValidator, \Countable $countable)
    {
        $emailValidator->validate(Argument::any(), Argument::type(Constraint::class))->willReturn($countable);
        $countable->count()->willReturn(1);

        $this
            ->shouldThrow(NotValidDataException::class)
            ->duringPostUserPreLoginByEmailAction($paramFetcher);
    }

    public function it_throws_an_exception_if_user_already_exists(UserService $userService, Request $request, User $user)
    {
        $userService->getOneBy(['email' => self::EMAIL_FAKE])->willReturn($user);

        $this
            ->shouldThrow(AlreadyExistsException::class)
            ->duringRegisterUserAction($request);
    }

    public function it_throws_an_exception_if_user_has_not_hotp(User $user)
    {
        $user->hasHOTP()->willReturn(false);

        $this
            ->shouldThrow(TwoFactorAuthDisabledException::class)
            ->duringSendHotpAction();
    }

    public function it_throws_an_exception_if_user_not_found_by_email(ParamFetcher $paramFetcher, UserService $userService, Request $request)
    {
        $userService->getOneBy(['email' => self::EMAIL_FAKE])->willReturn(null)->shouldBeCalled();

        $this
            ->shouldThrow(UserNotFoundException::class)
            ->duringPostUserLoginByEmailPinAction($paramFetcher, $request);
    }

    public function it_throws_an_exception_if_user_not_found_by_id(UserService $userService)
    {
        $userService->get(self::USER_ID_FAKE)->willReturn(null);

        $this
            ->shouldThrow(UserNotFoundException::class)
            ->duringGetUserByUserIdAction(self::USER_ID_FAKE);
    }

    public function it_throws_an_exception_if_user_not_instance_of_user(ParamFetcher $paramFetcher, EmailValidator $emailValidator, \Countable $countable)
    {
        $emailValidator->validate(Argument::any(), Argument::type(Constraint::class))->willReturn($countable);
        $countable->count()->willReturn(0);

        $this
            ->shouldThrow(InvalidCredentialsException::class)
            ->duringPostUserPreLoginByEmailAction($paramFetcher);
    }

    public function it_throws_an_exception_if_currency_not_found_by_id(CurrencyService $currencyService, ParamFetcher $paramFetcher)
    {
        $currencyService->get(self::CURRENCY_ID_FAKE)->willReturn(null)->shouldBeCalled();

        $this
            ->shouldThrow(NotFoundException::class)
            ->duringGenerateUserCryptoAddressAction($paramFetcher);
    }

    public function it_throws_an_exception_if_bank_not_found_by_slug(BankRepository $bankRepository, Request $request)
    {
        $bankRepository->findOneBy(['slug' => self::BANK_SLUG_FAKE])->willReturn(null)->shouldBeCalled();

        $this
            ->shouldThrow(NotFoundException::class)
            ->duringWithdrawAction($request);
    }
}
