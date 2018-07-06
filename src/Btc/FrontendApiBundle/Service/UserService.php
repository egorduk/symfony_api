<?php

namespace Btc\FrontendApiBundle\Service;

use Btc\CoreBundle\Entity\UserBusinessInfo;
use Btc\CoreBundle\Entity\UserPersonalInfo;
use Btc\CoreBundle\Helper\UserInfo;
use Btc\CoreBundle\Entity\Plan\Payment\LimitPlan;
use Btc\FrontendApiBundle\Events\AccountActivityEvents;
use Btc\FrontendApiBundle\Events\UserActivityEvent;
use Btc\FrontendApiBundle\Exception\Rest\VerificationPendingException;
use Btc\FrontendApiBundle\Form\PhonePinType;
use Btc\FrontendApiBundle\Repository\OrderRepository;
use Btc\FrontendApiBundle\Repository\UserPreferenceRepository;
use Btc\FrontendApiBundle\Repository\VerificationRepository;
use Btc\Component\Market\Service\FeeService;
use Btc\CoreBundle\Entity\Order;
use Btc\CoreBundle\Entity\PhoneVerification;
use Btc\CoreBundle\Entity\RestEntityInterface;
use Btc\CoreBundle\Entity\User;
use Btc\CoreBundle\Entity\Verification;
use Btc\CoreBundle\Entity\Wallet;
use Btc\CoreBundle\Util\GeneratorInterface;
use Btc\CoreBundle\Util\SeedGeneratorInterface;
use Btc\FrontendApiBundle\Classes\RestFile;
use Btc\FrontendApiBundle\Classes\RestSecurity;
use Btc\FrontendApiBundle\Classes\RestSession;
use Btc\FrontendApiBundle\Exception\Rest\InvalidFormException;
use Btc\FrontendApiBundle\Exception\Rest\NotValidDataException;
use Btc\FrontendApiBundle\Form\LoginType;
use Btc\FrontendApiBundle\Form\PreferencesType;
use Btc\FrontendApiBundle\Form\RegisterType;
use Btc\FrontendApiBundle\Form\TwoFactorAdditionType;
use Btc\FrontendApiBundle\Form\TwoFactorRemovalType;
use Btc\FrontendApiBundle\Form\UserVerificationType;
use Btc\FrontendApiBundle\Repository\UserBusinessInfoRepository;
use Btc\FrontendApiBundle\Repository\UserPersonalInfoRepository;
use Btc\FrontendApiBundle\Form\PhoneVerificationType;
use Doctrine\ORM\EntityManager;
use FOS\RestBundle\View\View;
use JMS\Serializer\SerializationContext;
use JMS\Serializer\Serializer;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface;
use Doctrine\Bundle\DoctrineBundle\Registry;

class UserService extends RestService
{
    private $em;
    private $entityClass;
    private $repository;
    private $formFactory;
    private $authService;
    private $encoderFactory;
    private $feeService;
    private $generator;
    private $walletService;
    private $serializer;
    private $registrationService;
    private $newsletterService;
    private $ed;
    private $userBusinessRepository;
    private $userPersonalRepository;
    private $verificationRepository;
    private $phoneService;
    private $sessionService;
    private $userPreferenceRepository;
    private $fileUploader;
    private $seedGenerator;
    private $notificationService;
    private $orderRepository;

    public function __construct(
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
        OrderRepository $orderRepository,
        $entityClass
    )
    {
        $this->em = $em;
        $this->entityClass = $entityClass;
        $this->repository = $this->em->getRepository($this->entityClass);
        $this->formFactory = $formFactory;
        $this->authService = $authService;
        $this->encoderFactory = $encoderFactory;
        $this->generator = $generator;
        $this->feeService = $feeService;
        $this->walletService = $walletService;
        $this->serializer = $serializer;
        $this->registrationService = $registrationService;
        $this->newsletterService = $newsletterService;
        $this->ed = $ed;
        $this->userBusinessRepository = $userBusinessRepository;
        $this->userPersonalRepository = $userPersonalRepository;
        $this->verificationRepository = $verificationRepository;
        $this->phoneService = $phoneService;
        $this->sessionService = $sessionService;
        $this->userPreferenceRepository = $userPreferenceRepository;
        $this->fileUploader = $fileUploader;
        $this->seedGenerator = $seedGenerator;
        $this->notificationService = $notificationService;
        $this->orderRepository = $orderRepository;

        parent::__construct($em, $entityClass);
    }

    /**
     * Processes register form.
     *
     * @param Request $request
     *
     * @return RestEntityInterface | null
     *
     * @throws NotValidDataException
     */
    public function processRegisterForm(Request $request)
    {
        $form = $this->formFactory->create(new RegisterType(), $this->createEntity());

        $parameters = $request->request->all();
        /**
         * @TODO remove when move
         */
        $parameters = array_intersect_key($parameters, ['email' => 1, 'newsletter' => 1]);

        $form->submit($parameters);

        if ($form->isValid()) {
            $user = $form->getData();

            if (!$user instanceof User) {
                return null;
            }

            $user->setUsername($this->generator->generateUsername());
            $user->setPassword($this->generator->generatePassword());

            $user = $this->walletService
                ->setWalletsForUser($user);

            $this->registrationService->assignDefaultPlans($user);
            $this->registrationService->assignDefaultPreferences($user);
            $this->registrationService->setNewsletterPreference($user, $form['newsletter']->getData());

            $user = $this->put($user);

            $this->newsletterService->updateSubscriptionByPreference($user);

            $this->ed->dispatch(
                AccountActivityEvents::REGISTRATION_COMPLETED,
                new UserActivityEvent($user, $request, ['%email%' => $user->getEmail()])
            );

            return $user;
        }

        throw new NotValidDataException();
    }

    /**
     * Processes login form.
     *
     * @param array $parameters
     *
     * @return RestEntityInterface | null
     *
     * @throws NotValidDataException
     */
    public function processLoginForm(array $parameters)
    {
        $form = $this->formFactory->create(new LoginType(), $this->createEntity(), ['csrf_protection' => false]);
        $form->submit($parameters);

        if ($form->isValid()) {
            $userData = $form->getData();

            $user = $this->repository
                ->findOneBy(['email' => $userData->getEmail()]);

            if (!$user instanceof User) {
                return null;
            }

            $isValidPassword = $this->encoderFactory
                ->getEncoder($user)
                ->isPasswordValid($user->getPassword(), $userData->getPassword(), $user->getSalt());

            if ($isValidPassword) {
                return $user;
            }

            return null;
        }

        throw new NotValidDataException();
    }

    /**
     * @param Request $request
     * @param Verification $verification
     *
     * @return RestEntityInterface | null
     *
     * @throws NotValidDataException
     */
    public function processUpdateUserProfileForm(Request $request, Verification $verification)
    {
        $parameters = $request->request->all();

        $form = $this->formFactory->create(new UserVerificationType(), $verification);
        $form->submit($parameters, false);

        if ($form->isValid()) {
            $verification = $form->getData();

            $user = $verification->getUser();

            $businessInfo = $verification->getBusinessInfo();
            $personalInfo = $verification->getPersonalInfo();

            $setNewPlan = null;

            $paymentPlanRep = $this->em->getRepository(LimitPlan::class);

            if (array_key_exists(UserPersonalInfo::CLASS_NAME, $parameters)) {
                if ($personalInfo->isPending()) {
                    throw new VerificationPendingException();
                } elseif ($personalInfo->isApproved()) {
                    if (!$businessInfo->isApproved()) {
                        $setNewPlan = $paymentPlanRep->findOneBySlug('unverified');
                    }

                    $user->removeRole(User::VERIFIED_PERSONAL);
                }

                $personalInfo = $this->handlePersonalInfoUploadingFiles($personalInfo);
                $personalInfo->setStatus(UserInfo::STATUS_PENDING);

                $this->userPersonalRepository->save($personalInfo);

                $verification->setPersonalInfo($personalInfo);
            }

            if (array_key_exists(UserBusinessInfo::CLASS_NAME, $parameters)) {
                if ($businessInfo->isPending()) {
                    throw new VerificationPendingException();
                } elseif ($businessInfo->isApproved()) {
                    if ($personalInfo->isApproved()) {
                        $setNewPlan = $paymentPlanRep->findOneBySlug('verified-personal');
                    } else  {
                        $setNewPlan = $paymentPlanRep->findOneBySlug('unverified');

                        $user->removeRole(User::VERIFIED_BUSINESS);
                    }
                }

                $businessInfo = $this->handleBusinessInfoUploadingFiles($businessInfo);
                $businessInfo->setStatus(UserInfo::STATUS_PENDING);

                $this->userBusinessRepository->save($businessInfo);

                $verification->setBusinessInfo($businessInfo);
            }

            //if user has changed his verification after approval - we may want to lower payment plan
            if (!empty($setNewPlan)) {
                $dql = <<<DQL
                SELECT a, p FROM BtcCoreBundle:Plan\Payment\LimitAssignment a
                JOIN a.plan p
                WHERE a.user = :user
DQL;
                $assignment = current($this->em->createQuery($dql)->setParameters(compact('user'))->getResult());
                $assignment->setPlan($setNewPlan);
                $this->em->persist($assignment);
            }

            $this->verificationRepository->save($verification);

            $user = $this->put($user);
            $user->setVerification($verification);

            $this->ed->dispatch(
                AccountActivityEvents::PROFILE_EDIT_COMPLETED,
                new UserActivityEvent($user, $request)
            );

            /* TODO fix mail template */
            $this->notificationService->notifyAboutVerification($verification);

            return $user;
        }

        throw new NotValidDataException();
    }

    /**
     * @param User $user
     *
     * @return View
     */
    public function getUserView(User $user)
    {
        $apiGroup = ['api'];

        $feeSet = $this->feeService->getFeeSetPercentByUser($user);

        $userOpenOrders = $this->orderRepository->getUserOpenOrderWithLimit($user);

        foreach ($userOpenOrders as $userOpenOrder) {
            $userOpenOrder->setAmountLeft($userOpenOrder->getAmountLeft());
        }

        $serializedUser = $this->serializer->serialize($user, 'json', SerializationContext::create()->setGroups($apiGroup));
        $user = $this->serializer->deserialize($serializedUser, User::class, 'json');

        $user->setFeeSet([
            'name' => $feeSet->getFeeName(),
            'type' => $feeSet->getFeeType(),
            'fees' => $feeSet->getMarketFeePercents(),
        ]);

        $serializedUserOpenOrders = $this->serializer->serialize($userOpenOrders, 'json', SerializationContext::create()->setGroups($apiGroup));
        $userOpenOrders = $this->serializer->deserialize($serializedUserOpenOrders, 'array<' . Order::class . '>', 'json');

        foreach ($userOpenOrders as $key => $userOpenOrder) {
            $userOpenOrder->setIsBuy($userOpenOrder->getSide() !== Order::SIDE_SELL);
            $userOpenOrder->setSide($userOpenOrder->getSide());
            $userOpenOrder->setMarketId($userOpenOrder->getMarket()->getId());
        }

        $userWallets = $user->getWallets();
        $serializedUserWallets = $this->serializer->serialize($userWallets, 'json', SerializationContext::create()->setGroups($apiGroup));
        $userWallets = $this->serializer->deserialize($serializedUserWallets, 'array<' . Wallet::class . '>', 'json');

        $view = new View([
            'user' => $user,
            'open_orders' => $userOpenOrders,
            'wallets' => $userWallets,
        ], Response::HTTP_OK);

        return $view;
    }

    public function processPhoneVerificationForm(Request $request, PhoneVerification $phoneVerification)
    {
        $form = $this->formFactory->create(new PhoneVerificationType(), $phoneVerification);

        $parameters = $request->request->all();

        $form->submit($parameters);

        if ($form->isValid()) {
            $phoneVerification = $this->phoneService->requestForValidation($phoneVerification);
            $phoneVerification = $this->phoneService->save($phoneVerification);

            return $phoneVerification;
        }

        throw new NotValidDataException();
    }

    public function processPhonePinVerificationForm(Request $request, User $user)
    {
        $form = $this->formFactory->create(new PhonePinType(), ['pin' => null]);

        $parameters = $request->request->all();

        $form->submit($parameters);

        if ($form->isValid()) {
            $verification = $this->phoneService->findAndConfirm($user, $form['pin']->getData());
            $verification = $this->enableSmsTwoFactor($verification);

            $this->ed->dispatch(
                AccountActivityEvents::TWO_FACTOR_ENABLED,
                new UserActivityEvent($user, $request)
            );

            return $verification;
        }

        throw new NotValidDataException();
    }

    public function enableSmsTwoFactor(PhoneVerification $verification)
    {
        $this->sessionService->set(RestSession::SESSION_HOTP_AUTHENTICATED, true);

        return $this->phoneService->enableSmsTwoFactor($verification);
    }

    public function processChangeUserPreferencesForm(Request $request, User $user)
    {
        $form = $this->formFactory->create(new PreferencesType());

        $parameters = $request->request->all();

        unset($parameters['_format']);

        $form->submit($parameters);

        if ($form->isValid()) {
            foreach ($form['rows'] as $row) {
                $preference = $row->getData();

                $this->userPreferenceRepository->save($preference);
            }

            $this->userPreferenceRepository->save(null, true);

            $this->ed->dispatch(
                AccountActivityEvents::PREFERENCES_UPDATED,
                new UserActivityEvent($user, $request)
            );

            $this->newsletterService->updateSubscriptionByPreference($user);

            return $user;
        }

        throw new NotValidDataException();
    }

    public function processGoogleAuthForm(Request $request, User $user, $mode = '')
    {
        $formType = $mode === RestSecurity::GOOGLE_AUTH_TURN_ON ?
            new TwoFactorAdditionType() :
            new TwoFactorRemovalType();

        $form = $this->formFactory->create($formType, $user);

        $form->submit($request);

        if ($form->isValid()) {
            $this->sessionService->set('totp_authenticated', $mode === RestSecurity::GOOGLE_AUTH_TURN_ON ?: false);

            $mode === RestSecurity::GOOGLE_AUTH_TURN_ON ?
                $user->setAuthKey(true) :
                $user->setAuthKey(null);

            $this->put($user);

            $this->ed->dispatch(
                $mode === RestSecurity::GOOGLE_AUTH_TURN_ON ?
                    AccountActivityEvents::TWO_FACTOR_ENABLED :
                    AccountActivityEvents::TWO_FACTOR_DISABLED,
                new UserActivityEvent($user, $request)
            );

            return $user;
        }

        throw new NotValidDataException();
    }

    public function setNewAuthKey(User $user)
    {
        $user->setAuthKey($this->seedGenerator->getSeed());

        return $this->put($user);
    }

    public function blockedUser(User $user)
    {
        $user->addRole(User::BLOCKED);
        $user->setPin('');
        $user->setToken('');

        return $this->put($user);
    }

    private function handleBusinessInfoUploadingFiles(UserBusinessInfo $businessInfo)
    {
        if ($businessInfo->getCompanyDetails1Content() && $businessInfo->getCompanyDetails1Name()) {
            $restFile = new RestFile($businessInfo->getCompanyDetails1Name(), $businessInfo->getCompanyDetails1Content());

            $attachment = $this->fileUploader->uploadFile($restFile);
            $businessInfo->setCompanyDetails1($attachment);
        }

        if ($businessInfo->getCompanyDetails2Content() && $businessInfo->getCompanyDetails2Name()) {
            $restFile = new RestFile($businessInfo->getCompanyDetails2Name(), $businessInfo->getCompanyDetails2Content());

            $attachment = $this->fileUploader->uploadFile($restFile);
            $businessInfo->setCompanyDetails2($attachment);
        }

        if ($businessInfo->getCompanyDetails3Content() && $businessInfo->getCompanyDetails3Name()) {
            $restFile = new RestFile($businessInfo->getCompanyDetails3Name(), $businessInfo->getCompanyDetails3Content());

            $attachment = $this->fileUploader->uploadFile($restFile);
            $businessInfo->setCompanyDetails3($attachment);
        }

        if ($businessInfo->getCompanyDetails4Content() && $businessInfo->getCompanyDetails4Name()) {
            $restFile = new RestFile($businessInfo->getCompanyDetails4Name(), $businessInfo->getCompanyDetails4Content());

            $attachment = $this->fileUploader->uploadFile($restFile);
            $businessInfo->setCompanyDetails4($attachment);
        }

        return $businessInfo;
    }

    private function handlePersonalInfoUploadingFiles(UserPersonalInfo $personalInfo)
    {
        if ($personalInfo->getIdPhotoContent() && $personalInfo->getIdPhotoName()) {
            $restFile = new RestFile($personalInfo->getIdPhotoName(), $personalInfo->getIdPhotoContent());

            $attachment = $this->fileUploader->uploadFile($restFile);
            $personalInfo->setIdPhoto($attachment);
        }

        if ($personalInfo->getResidenceProofContent() && $personalInfo->getResidenceProofName()) {
            $restFile = new RestFile($personalInfo->getResidenceProofName(), $personalInfo->getResidenceProofContent());

            $attachment = $this->fileUploader->uploadFile($restFile);
            $personalInfo->setResidenceProof($attachment);
        }

        if ($personalInfo->getIdBackSideContent() && $personalInfo->getIdBackSideName()) {
            $restFile = new RestFile($personalInfo->getIdBackSideName(), $personalInfo->getIdBackSideContent());

            $attachment = $this->fileUploader->uploadFile($restFile);
            $personalInfo->setIdBackSide($attachment);
        }

        return $personalInfo;
    }
}
