<?php

namespace spec\Btc\FrontendApiBundle\Service;

use Btc\Component\Market\Model\FeeSet;
use Btc\Component\Market\Service\FeeService;
use Btc\CoreBundle\Entity\Market;
use Btc\CoreBundle\Entity\Order;
use Btc\CoreBundle\Entity\PhoneVerification;
use Btc\CoreBundle\Entity\Plan\Payment\LimitPlan;
use Btc\CoreBundle\Entity\User;
use Btc\CoreBundle\Entity\UserBusinessInfo;
use Btc\CoreBundle\Entity\UserPersonalInfo;
use Btc\CoreBundle\Entity\UserPreference;
use Btc\CoreBundle\Entity\Verification;
use Btc\CoreBundle\Entity\Wallet;
use Btc\CoreBundle\Util\GeneratorInterface;
use Btc\CoreBundle\Util\SeedGeneratorInterface;
use Btc\FrontendApiBundle\Classes\RestSecurity;
use Btc\FrontendApiBundle\Events\AccountActivityEvents;
use Btc\FrontendApiBundle\Events\UserActivityEvent;
use Btc\FrontendApiBundle\Exception\Rest\NotValidDataException;
use Btc\FrontendApiBundle\Form\LoginType;
use Btc\FrontendApiBundle\Form\PhonePinType;
use Btc\FrontendApiBundle\Form\PhoneVerificationType;
use Btc\FrontendApiBundle\Form\PreferencesType;
use Btc\FrontendApiBundle\Form\RegisterType;
use Btc\FrontendApiBundle\Form\TwoFactorAdditionType;
use Btc\FrontendApiBundle\Form\TwoFactorRemovalType;
use Btc\FrontendApiBundle\Form\UserVerificationType;
use Btc\FrontendApiBundle\Repository\OrderRepository;
use Btc\FrontendApiBundle\Repository\PhoneRepository;
use Btc\FrontendApiBundle\Repository\UserBusinessInfoRepository;
use Btc\FrontendApiBundle\Repository\UserPersonalInfoRepository;
use Btc\FrontendApiBundle\Repository\UserPreferenceRepository;
use Btc\FrontendApiBundle\Repository\UserRepository;
use Btc\FrontendApiBundle\Repository\VerificationRepository;
use Btc\FrontendApiBundle\Service\AuthService;
use Btc\FrontendApiBundle\Service\NewsletterService;
use Btc\FrontendApiBundle\Service\NotificationsService;
use Btc\FrontendApiBundle\Service\PhoneService;
use Btc\FrontendApiBundle\Service\RestService;
use Btc\FrontendApiBundle\Service\UserRegistrationService;
use Btc\FrontendApiBundle\Service\UserService;
use Btc\FrontendApiBundle\Service\VerificationUploaderService;
use Btc\FrontendApiBundle\Service\WalletService;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use FOS\RestBundle\View\View;
use JMS\Serializer\SerializationContext;
use JMS\Serializer\Serializer;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface;
use Symfony\Component\Security\Core\Encoder\PasswordEncoderInterface;

class UserServiceSpec extends ObjectBehavior
{
    const ID_FAKE = 1;
    const USER_NAME_FAKE = 'username';
    const USER_PASSWORD_FAKE = 'password';
    const USER_SALT_FAKE = 'salt';
    const FEE_SET_NAME_FAKE = 'name';
    const FEE_SET_TYPE_FAKE = 'type';
    const FEE_SET_PERCENT_FAKE = 1;
    const ORDER_SIDE_FAKE = 'side';
    const USER_EMAIL_FAKE = 'email';

    public function let(
        EntityManager $em,
        FormFactoryInterface $formFactory,
        AuthService $authService,
        EncoderFactoryInterface $encoderFactory,
        GeneratorInterface $generator,
        FeeService $feeService,
        WalletService $walletService,
        Serializer $serializer,
        UserRegistrationService $registrationService,
        NewsletterService $newsletterService,
        EventDispatcherInterface $ed,
        UserBusinessInfoRepository $userBusinessRepository,
        UserPersonalInfoRepository $userPersonalRepository,
        VerificationRepository $verificationRepository,
        PhoneService $phoneService,
        Session $sessionService,
        UserPreferenceRepository $userPreferenceRepository,
        VerificationUploaderService $fileUploader,
        SeedGeneratorInterface $seedGenerator,
        NotificationsService $notificationService,
        User $user,
        Request $request,
        ParameterBag $parameterBag,
        UserRepository $userRepository,
        OrderRepository $orderRepository,
        FeeSet $feeSet,
        Order $order,
        Market $market
    ) {
        $request->request = $parameterBag;
        $parameterBag->all()->willReturn([]);

        $feeSet->getFeeName()->willReturn(self::FEE_SET_NAME_FAKE);
        $feeSet->getFeeType()->willReturn(self::FEE_SET_TYPE_FAKE);
        $feeSet->getMarketFeePercents()->willReturn(self::FEE_SET_PERCENT_FAKE);

        $order->getMarket()->willReturn($market);
        $order->getSide()->willReturn();

        $market->getId()->willReturn(self::ID_FAKE);

        $em->getRepository($user)->willReturn($userRepository);

        $this->beConstructedWith(
            $em,
            $formFactory,
            $authService,
            $encoderFactory,
            $generator,
            $feeService,
            $walletService,
            $serializer,
            $registrationService,
            $newsletterService,
            $ed,
            $userBusinessRepository,
            $userPersonalRepository,
            $verificationRepository,
            $phoneService,
            $sessionService,
            $userPreferenceRepository,
            $fileUploader,
            $seedGenerator,
            $notificationService,
            $orderRepository,
            $user
        );
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(UserService::class);
        $this->shouldHaveType(RestService::class);
    }

    public function it_should_create_entity()
    {
        $this->createEntity()->shouldHaveType(User::class);
    }

    public function it_should_process_register_form(
        Request $request,
        FormFactoryInterface $formFactory,
        FormInterface $form,
        User $user,
        WalletService $walletService,
        GeneratorInterface $generator,
        UserRegistrationService $userRegistrationService,
        UserRepository $userRepository,
        NewsletterService $newsletterService,
        EventDispatcher $ed
    ) {
        $formFactory
            ->create(Argument::type(RegisterType::class), Argument::type(User::class))
            ->willReturn($form)
            ->shouldBeCalled();

        $form->submit([])->shouldBeCalled();
        $form->isValid()->willReturn(true);
        $form->getData()->willReturn($user);

        $generator->generateUsername()->willReturn(self::USER_NAME_FAKE)->shouldBeCalled();
        $generator->generatePassword()->willReturn(self::USER_PASSWORD_FAKE)->shouldBeCalled();

        $user->getEmail()->willReturn(self::USER_EMAIL_FAKE);

        $user->setUsername(self::USER_NAME_FAKE)->shouldBeCalled()->shouldBeCalled();
        $user->setPassword(self::USER_PASSWORD_FAKE)->shouldBeCalled()->shouldBeCalled();

        $walletService->setWalletsForUser($user)->willReturn($user)->shouldBeCalled();

        $userRegistrationService->assignDefaultPlans($user);
        $userRegistrationService->assignDefaultPreferences($user);

        $form->getData()->willReturn($user);
        $form->offsetGet('newsletter')->willReturn($form);

        $userRegistrationService->setNewsletterPreference(Argument::type(User::class), Argument::any());

        $userRepository->save($user, Argument::exact(true))->willReturn($user)->shouldBeCalled();

        $newsletterService->updateSubscriptionByPreference($user)->shouldBeCalled();

        $ed->dispatch(
            AccountActivityEvents::REGISTRATION_COMPLETED,
            Argument::type(UserActivityEvent::class)
        )->shouldBeCalled();

        $this->processRegisterForm($request)->shouldReturn($user);
    }

    public function it_should_throw_while_process_register_form(
        Request $request,
        FormFactoryInterface $formFactory,
        FormInterface $form
    ) {
        $formFactory
            ->create(Argument::type(RegisterType::class), Argument::type(User::class))
            ->willReturn($form)
            ->shouldBeCalled();

        $form->submit([])->shouldBeCalled();
        $form->isValid()->willReturn(false);

        $this
            ->shouldThrow(NotValidDataException::class)
            ->duringProcessRegisterForm($request);
    }

    public function it_should_process_login_form(
        FormFactoryInterface $formFactory,
        FormInterface $form,
        User $user,
        UserRepository $userRepository,
        EncoderFactoryInterface $encoderFactory,
        PasswordEncoderInterface $passwordEncoder
    ) {
        $formFactory
            ->create(Argument::type(LoginType::class), Argument::type(User::class), Argument::type('array'))
            ->willReturn($form)
            ->shouldBeCalled();

        $form->submit([])->shouldBeCalled();
        $form->isValid()->willReturn(true);
        $form->getData()->willReturn($user);

        $user->getEmail()->willReturn(self::USER_EMAIL_FAKE)->shouldBeCalled();
        $user->getPassword()->willReturn(self::USER_PASSWORD_FAKE);
        $user->getSalt()->willReturn(self::USER_SALT_FAKE);

        $userRepository->findOneBy(['email' => self::USER_EMAIL_FAKE])->willReturn($user)->shouldBeCalled();

        $encoderFactory->getEncoder($user)->willReturn($passwordEncoder);

        $passwordEncoder
            ->isPasswordValid(Argument::any(), Argument::any(), Argument::any())
            ->shouldBeCalled()
            ->willReturn(true);

        $this->processLoginForm([])->shouldReturn($user);
    }

    public function it_should_throw_while_process_login_form(
        FormFactoryInterface $formFactory,
        FormInterface $form
    ) {
        $formFactory
            ->create(Argument::type(LoginType::class), Argument::type(User::class), Argument::type('array'))
            ->willReturn($form)
            ->shouldBeCalled();

        $form->submit([])->shouldBeCalled();
        $form->isValid()->willReturn(false);

        $this
            ->shouldThrow(NotValidDataException::class)
            ->duringProcessLoginForm([]);
    }

    public function it_should_return_null_if_user_password_is_not_valid_while_process_login_form(
        FormFactoryInterface $formFactory,
        FormInterface $form,
        User $user,
        UserRepository $userRepository,
        EncoderFactoryInterface $encoderFactory,
        PasswordEncoderInterface $passwordEncoder
    ) {
        $formFactory
            ->create(Argument::type(LoginType::class), Argument::type(User::class), Argument::type('array'))
            ->willReturn($form)
            ->shouldBeCalled();

        $form->submit([])->shouldBeCalled();
        $form->isValid()->willReturn(true);
        $form->getData()->willReturn($user);

        $user->getEmail()->willReturn(self::USER_EMAIL_FAKE);
        $user->getPassword()->willReturn(self::USER_PASSWORD_FAKE);
        $user->getSalt()->willReturn(self::USER_SALT_FAKE);

        $userRepository->findOneBy(['email' => self::USER_EMAIL_FAKE])->willReturn($user)->shouldBeCalled();

        $encoderFactory->getEncoder($user)->willReturn($passwordEncoder);

        $passwordEncoder
            ->isPasswordValid(Argument::any(), Argument::any(), Argument::any())
            ->shouldBeCalled()
            ->willReturn(false);

        $this->processLoginForm([])->shouldReturn(null);
    }

    public function it_should_process_update_user_profile_form(
        FormFactoryInterface $formFactory,
        FormInterface $form,
        User $user,
        UserRepository $userRepository,
        Request $request,
        Verification $verification,
        UserPersonalInfo $userPersonalInfo,
        UserBusinessInfo $userBusinessInfo,
        EventDispatcher $ed,
        NotificationsService $notificationsService,
        EntityRepository $repo,
        EntityManager $em
    ) {
        $formFactory
            ->create(Argument::type(UserVerificationType::class), Argument::type(Verification::class))
            ->willReturn($form)
            ->shouldBeCalled();

        $form->submit([], Argument::exact(false))->shouldBeCalled();
        $form->isValid()->willReturn(true);
        $form->getData()->willReturn($verification);

        $user->__toString()->willReturn('u_1')->shouldBeCalled();

        $verification->getUser()->willReturn($user);

        $em->getRepository(LimitPlan::class)->willReturn($repo);

        $verification->getBusinessInfo()->willReturn($userBusinessInfo);
        $verification->getPersonalInfo()->willReturn($userPersonalInfo);

        $user->setVerification(Argument::type(Verification::class))->shouldBeCalled();

        $userRepository->save($user, Argument::exact(true))->willReturn($user)->shouldBeCalled();

        $ed->dispatch(
            AccountActivityEvents::PROFILE_EDIT_COMPLETED,
            Argument::type(UserActivityEvent::class)
        )->shouldBeCalled();

        $notificationsService->notifyAboutVerification(Argument::type(Verification::class));

        $this->processUpdateUserProfileForm($request, $verification)->shouldReturn($user);
    }

    public function it_should_throw_while_process_update_user_profile_form(
        FormFactoryInterface $formFactory,
        FormInterface $form,
        Verification $verification,
        Request $request
    ) {
        $formFactory
            ->create(Argument::type(UserVerificationType::class), Argument::type(Verification::class))
            ->willReturn($form)
            ->shouldBeCalled();

        $form->submit([], Argument::exact(false))->shouldBeCalled();
        $form->isValid()->willReturn(false);

        $this
            ->shouldThrow(NotValidDataException::class)
            ->duringProcessUpdateUserProfileForm($request, $verification);
    }

    public function it_should_get_user_view(
        User $user,
        OrderRepository $orderRepository,
        FeeService $feeService,
        FeeSet $feeSet,
        Serializer $serializer,
        Order $order
    ) {
        $user->getWallets()->willReturn([])->shouldBeCalled();

        $feeService->getFeeSetPercentByUser(Argument::type(User::class))->willReturn($feeSet)->shouldBeCalled();

        $orderRepository->getUserOpenOrderWithLimit(Argument::type(User::class))->willReturn([])->shouldBeCalled();

        $serializer
            ->serialize($user, Argument::exact('json'), Argument::exact(SerializationContext::create()->setGroups(['api'])))
            ->willReturn($user)
            ->shouldBeCalled();

        $serializer
            ->deserialize($user, Argument::exact(User::class), Argument::exact('json'))
            ->willReturn($user)
            ->shouldBeCalled();

        $user->setFeeSet([
            'name' => self::FEE_SET_NAME_FAKE,
            'type' => self::FEE_SET_TYPE_FAKE,
            'fees' => self::FEE_SET_PERCENT_FAKE,
        ])->shouldBeCalled();

        $serializer
            ->serialize([], Argument::exact('json'), SerializationContext::create()->setGroups(['api']))
            ->willReturn([])
            ->shouldBeCalled();

        $serializer
            ->deserialize([], 'array<'.Order::class.'>', Argument::exact('json'))
            ->willReturn([$order])
            ->shouldBeCalled();

        $order->setIsBuy(true)->shouldBeCalled();
        $order->setSide(null)->shouldBeCalled();
        $order->setMarketId(self::ID_FAKE)->shouldBeCalled();

        $serializer
            ->serialize([], Argument::exact('json'), SerializationContext::create()->setGroups(['api']))
            ->willReturn([])
            ->shouldBeCalled();

        $serializer
            ->deserialize([], 'array<'.Wallet::class.'>', Argument::exact('json'))
            ->willReturn([])
            ->shouldBeCalled();

        $this->getUserView($user)->shouldHaveType(View::class);
    }


    public function it_should_process_phone_verification_form(
        FormFactoryInterface $formFactory,
        FormInterface $form,
        Request $request,
        PhoneVerification $phoneVerification,
        PhoneRepository $phoneRepository,
        PhoneService $phoneService
    ) {
        $formFactory
            ->create(Argument::type(PhoneVerificationType::class), Argument::type(PhoneVerification::class))
            ->willReturn($form)
            ->shouldBeCalled();

        $form->submit([])->shouldBeCalled();
        $form->isValid()->willReturn(true);

        $phoneService->requestForValidation(Argument::type(PhoneVerification::class))->willReturn($phoneVerification);
        $phoneService->save($phoneVerification)->willReturn($phoneVerification)->shouldBeCalled();

        $phoneRepository->save($phoneVerification, Argument::exact(true))->willReturn($phoneVerification);

        $this->processPhoneVerificationForm($request, $phoneVerification)->shouldReturn($phoneVerification);
    }

    public function it_should_throw_while_process_phone_verification_form(
        FormFactoryInterface $formFactory,
        FormInterface $form,
        Request $request,
        PhoneVerification $phoneVerification
    ) {
        $formFactory
            ->create(Argument::type(PhoneVerificationType::class), Argument::type(PhoneVerification::class))
            ->willReturn($form)
            ->shouldBeCalled();

        $form->submit([])->shouldBeCalled();
        $form->isValid()->willReturn(false);


        $this
            ->shouldThrow(NotValidDataException::class)
            ->duringProcessPhoneVerificationForm($request, $phoneVerification);
    }

    public function it_should_process_phone_pin_verification_form(
        FormFactoryInterface $formFactory,
        FormInterface $form,
        Request $request,
        PhoneVerification $phoneVerification,
        User $user,
        PhoneService $phoneService,
        EventDispatcher $ed
    ) {
        $formFactory
            ->create(Argument::type(PhonePinType::class), Argument::type('array'))
            ->willReturn($form)
            ->shouldBeCalled();

        $form->submit([])->shouldBeCalled();
        $form->isValid()->willReturn(true);

        $form->getData()->willReturn($phoneVerification);
        $form->offsetGet('pin')->willReturn($form);

        $phoneService->findAndConfirm($user, Argument::any())->willReturn($phoneVerification)->shouldBeCalled();
        $phoneService->enableSmsTwoFactor($phoneVerification)->willReturn($phoneVerification)->shouldBeCalled();

        $ed->dispatch(
            AccountActivityEvents::TWO_FACTOR_ENABLED,
            Argument::type(UserActivityEvent::class)
        )->shouldBeCalled();

        $this->processPhonePinVerificationForm($request, $user)->shouldReturn($phoneVerification);
    }

    public function it_should_throw_while_process_phone_pin_verification_form(
        FormFactoryInterface $formFactory,
        FormInterface $form,
        Request $request,
        User $user
    ) {
        $formFactory
            ->create(Argument::type(PhonePinType::class), Argument::type('array'))
            ->willReturn($form)
            ->shouldBeCalled();

        $form->submit([])->shouldBeCalled();
        $form->isValid()->willReturn(false);

        $this
            ->shouldThrow(NotValidDataException::class)
            ->duringProcessPhonePinVerificationForm($request, $user);
    }

    public function it_should_process_change_user_preferences_form(
        FormFactoryInterface $formFactory,
        FormInterface $form,
        Request $request,
        User $user,
        EventDispatcher $ed,
        UserPreference $userPreference,
        UserPreferenceRepository $userPreferenceRepository,
        NewsletterService $newsletterService
    ) {
        $formFactory
            ->create(Argument::type(PreferencesType::class))
            ->willReturn($form)
            ->shouldBeCalled();

        $form->submit([])->shouldBeCalled();
        $form->isValid()->willReturn(true);

        $form->getData()->willReturn($userPreference);
        $form->offsetGet('rows')->willReturn($form);
        $form->rewind()->willReturn($form);
        $form->valid()->willReturn(false);

        $userPreferenceRepository->save(null, true)->shouldBeCalled();

        $newsletterService->updateSubscriptionByPreference($user)->shouldBeCalled();

        $ed->dispatch(
            AccountActivityEvents::PREFERENCES_UPDATED,
            Argument::type(UserActivityEvent::class)
        )->shouldBeCalled();

        $this->processChangeUserPreferencesForm($request, $user)->shouldReturn($user);
    }

    public function it_should_throw_while_process_change_user_preferences_form(
        FormFactoryInterface $formFactory,
        FormInterface $form,
        Request $request,
        User $user
    ) {
        $formFactory
            ->create(Argument::type(PreferencesType::class))
            ->willReturn($form)
            ->shouldBeCalled();

        $form->submit([])->shouldBeCalled();
        $form->isValid()->willReturn(false);

        $this
            ->shouldThrow(NotValidDataException::class)
            ->duringProcessChangeUserPreferencesForm($request, $user);
    }

    public function it_should_process_turn_on_google_auth_form(
        FormFactoryInterface $formFactory,
        FormInterface $form,
        Request $request,
        User $user,
        EventDispatcher $ed,
        UserRepository $userRepository
    ) {
        $formFactory
            ->create(Argument::type(TwoFactorAdditionType::class), Argument::type(User::class))
            ->willReturn($form)
            ->shouldBeCalled();

        $form->submit($request)->shouldBeCalled();
        $form->isValid()->willReturn(true);

        $user->setAuthKey(true)->shouldBeCalled();

        $userRepository->save($user, true)->shouldBeCalled();

        $ed->dispatch(
            AccountActivityEvents::TWO_FACTOR_ENABLED,
            Argument::type(UserActivityEvent::class)
        )->shouldBeCalled();

        $this->processGoogleAuthForm($request, $user, RestSecurity::GOOGLE_AUTH_TURN_ON)->shouldReturn($user);
    }

    public function it_should_process_turn_off_google_auth_form(
        FormFactoryInterface $formFactory,
        FormInterface $form,
        Request $request,
        User $user,
        EventDispatcher $ed,
        UserRepository $userRepository
    ) {
        $formFactory
            ->create(Argument::type(TwoFactorRemovalType::class), Argument::type(User::class))
            ->willReturn($form)
            ->shouldBeCalled();

        $form->submit($request)->shouldBeCalled();
        $form->isValid()->willReturn(true);

        $user->setAuthKey(null)->shouldBeCalled();

        $userRepository->save($user, true)->shouldBeCalled();

        $ed->dispatch(
            AccountActivityEvents::TWO_FACTOR_DISABLED,
            Argument::type(UserActivityEvent::class)
        )->shouldBeCalled();

        $this->processGoogleAuthForm($request, $user, RestSecurity::GOOGLE_AUTH_TURN_OFF)->shouldReturn($user);
    }

    public function it_should_throw_while_process_google_auth_form(
        FormFactoryInterface $formFactory,
        FormInterface $form,
        Request $request,
        User $user
    ) {
        $formFactory
            ->create(Argument::type(TwoFactorRemovalType::class), Argument::type(User::class))
            ->willReturn($form)
            ->shouldBeCalled();

        $form->submit($request)->shouldBeCalled();
        $form->isValid()->willReturn(false);

        $this
            ->shouldThrow(NotValidDataException::class)
            ->duringProcessGoogleAuthForm($request, $user);
    }

    public function it_should_set_auth_key(User $user, SeedGeneratorInterface $generator, UserRepository $userRepository)
    {
        $user->setAuthKey(null)->shouldBeCalled();

        $userRepository->save($user, true)->willReturn($user)->shouldBeCalled();

        $this->setNewAuthKey($user)->shouldBe($user);
    }

    public function it_should_block_user(User $user, UserRepository $userRepository)
    {
        $user->addRole(User::BLOCKED)->shouldBeCalled();
        $user->setPin('')->shouldBeCalled();
        $user->setToken('')->shouldBeCalled();

        $userRepository->save($user, true)->willReturn($user)->shouldBeCalled();

        $this->blockedUser($user)->shouldBe($user);
    }
}
