<?php

namespace Btc\FrontendApiBundle\Service;

use Btc\FrontendApiBundle\Exception\Rest\HotpSentTimesExceededException;
use Btc\FrontendApiBundle\Exception\Rest\NotFoundException;
use Btc\FrontendApiBundle\Exception\Rest\SmsSendingException;
use Btc\FrontendApiBundle\Exception\Rest\UnknownErrorException;
use Btc\CoreBundle\Entity\PhoneVerification;
use Btc\CoreBundle\Entity\User;
use Btc\FrontendApiBundle\Repository\PhoneRepository;
use Btc\FrontendApiBundle\Repository\UserRepository;
use Rych\OTP\Seed;
use Rych\OTP\HOTP;

class PhoneService
{
    const SEND_ATTEMPT_CNT = 5;
    const PIN_LENGTH = 6;

    private $phoneRepository;
    private $userRepository;
    private $sms;
    private $pin;

    public function __construct(
        SmsService $sms,
        PinInterface $pin,
        PhoneRepository $phoneRepository,
        UserRepository $userRepository
    ) {
        $this->phoneRepository = $phoneRepository;
        $this->userRepository = $userRepository;
        $this->sms = $sms;
        $this->pin = $pin;
    }

    /**
     * @param PhoneVerification $verification
     *
     * @return PhoneVerification
     */
    public function requestForValidation(PhoneVerification $verification)
    {
        $this->phoneRepository->deleteAllUserVerifications($verification->getUser());
        $verification = $this->regeneratePinAndPersistVerification($verification);

        return $this->sendVerification($verification);
    }

    public function deleteVerification(PhoneVerification $verification)
    {
        if ($verification->getUser()->hasHOTP()) {
            $this->disableSmsTwoFactor($verification);
        }

        $this->phoneRepository->deleteVerification($verification);
    }

    public function enableSmsTwoFactor(PhoneVerification $verification)
    {
        $user = $verification->getUser();
        $user->setHotpAuthKey(Seed::generate());
        $user->setHotpAuthCounter(0);

        $this->userRepository->save($user, true);

        return $this->regeneratePinAndPersistVerification($verification);
    }

    public function disableSmsTwoFactor(PhoneVerification $verification)
    {
        $user = $verification->getUser();
        $user->setHotpAuthKey(null);
        $user->setHotpAuthCounter(null);
        $user->setHotpSentTimes(null);

        $this->userRepository->save($user, true);

        $this->regeneratePinAndPersistVerification($verification);
    }

    public function sendHotp(User $user)
    {
        if ($user->getHotpSentTimes() >= self::SEND_ATTEMPT_CNT) {
            throw new HotpSentTimesExceededException();
        }

        $verification = $this->phoneRepository->findConfirmed($user);

        if (!$verification instanceof PhoneVerification) {
            throw new NotFoundException();
        }

        $otp = new HOTP($user->getHotpAuthKey());

        $user->setHotpSentTimes($user->getHotpSentTimes() + 1);

        $this->userRepository->save($user, true);

        $message = $otp->calculate($user->getHotpAuthCounter());

        $this->sms->send($verification->getPhone(), $message);
    }

    /**
     * @param PhoneVerification $verification
     *
     * @return PhoneVerification
     */
    public function sendVerification(PhoneVerification $verification)
    {
        $isSent = $this->sms->send($verification->getPhone(), $verification->getPin());

        $verification->setSent($isSent);

        return $verification;
    }

    /**
     * @param PhoneVerification $verification
     *
     * @return PhoneVerification
     */
    private function regeneratePinAndPersistVerification(PhoneVerification $verification)
    {
        $verification->setPin($this->pin->generate(self::PIN_LENGTH));
        $verification->setSent(false);

        return $this->save($verification);
    }

    public function save(PhoneVerification $verification)
    {
        return $this->phoneRepository->save($verification, true);
    }

    public function findAndConfirm(User $user, $pin)
    {
        if ($this->phoneRepository->isVerified($user)) {
            throw new UnknownErrorException();
        }

        $requestedVerification = $this->phoneRepository->findRequestedByUserAndPin($user, $pin);

        if (!$requestedVerification instanceof PhoneVerification) {
            throw new NotFoundException();
        }

        $requestedVerification->confirm();

        return $requestedVerification;
    }

    public function incHotpCounter(User $user)
    {
        if ($user->hasHOTP()) {
            $user->setHotpAuthCounter($user->getHotpAuthCounter() + 1);
            $user->setHotpSentTimes(0);

            $this->userRepository->save($user, true);
        }
    }
}
