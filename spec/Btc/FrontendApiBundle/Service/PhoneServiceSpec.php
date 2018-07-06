<?php

namespace spec\Btc\FrontendApiBundle\Service;

use Btc\FrontendApiBundle\Exception\Rest\HotpSentTimesExceededException;
use Btc\FrontendApiBundle\Repository\PhoneRepository;
use Btc\FrontendApiBundle\Repository\UserRepository;
use Btc\FrontendApiBundle\Service\PhoneService;
use Btc\FrontendApiBundle\Service\PinInterface;
use Btc\FrontendApiBundle\Service\SmsService;
use Btc\CoreBundle\Entity\PhoneVerification;
use Btc\CoreBundle\Entity\User;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class PhoneServiceSpec extends ObjectBehavior
{
    const USER_ID_FAKE = 1;
    const PHONE_FAKE = '37060300000';
    const PIN_FAKE = '0000';

    public function let(
        SmsService $sms,
        PinInterface $pin,
        PhoneRepository $phoneRepository,
        User $user,
        PhoneVerification $verification,
        UserRepository $userRepository
    ) {
        $verification->getUser()->willReturn($user);
        $verification->getPhone()->willReturn(self::PHONE_FAKE);
        $verification->getPin()->willReturn(self::PIN_FAKE);

        $user->getId()->willReturn(self::USER_ID_FAKE);

        $phoneRepository->isVerified(Argument::any())->willReturn(false);

        $this->beConstructedWith($sms, $pin, $phoneRepository, $userRepository);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(PhoneService::class);
    }

    public function it_should_send_a_sms_with_pin_to_phone(
        SmsService $sms,
        PinInterface $pin,
        PhoneRepository $phoneRepository,
        PhoneVerification $verification,
        User $user
    ) {
        $pin->generate(Argument::any())->willReturn(self::PIN_FAKE);

        $verification->setPin(self::PIN_FAKE)->shouldBeCalled();
        $verification->setSent(false)->shouldBeCalled();

        $phoneRepository->deleteAllUserVerifications($user)->shouldBeCalled();
        $phoneRepository->save($verification, true)->willReturn($verification)->shouldBeCalled();

        $sms->send(self::PHONE_FAKE, Argument::exact(self::PIN_FAKE))->shouldBeCalled();

        $verification->setSent(true)->shouldBeCalled();

        $this->requestForValidation($verification);
    }

    public function it_should_enable_two_factor_if_phone_verification_is_confirmed(
        User $user,
        PhoneVerification $verification,
        PinInterface $pin,
        PhoneRepository $phoneRepository,
        UserRepository $userRepository
    ) {
        $user->setHotpAuthKey(Argument::any())->shouldBeCalled();
        $user->setHotpAuthCounter(0)->shouldBeCalled();

        $userRepository->save($user, true)->shouldBeCalled();

        $pin->generate(Argument::any())->willReturn(self::PIN_FAKE);

        $verification->setPin(self::PIN_FAKE)->shouldBeCalled();
        $verification->setSent(false)->shouldBeCalled();

        $phoneRepository->save($verification, true)->willReturn($verification)->shouldBeCalled();

        $this->enableSmsTwoFactor($verification);
    }

    public function it_should_disable_two_factor_if_phone_verification_is_confirmed(
        User $user,
        PhoneVerification $verification,
        PinInterface $pin,
        UserRepository $userRepository,
        PhoneRepository $phoneRepository
    ) {
        $verification->getUser()->willReturn($user);

        $user->setHotpAuthKey(null)->shouldBeCalled();
        $user->setHotpAuthCounter(null)->shouldBeCalled();
        $user->setHotpSentTimes(null)->shouldBeCalled();

        $userRepository->save($user, true)->shouldBeCalled();

        $pin->generate(Argument::any())->willReturn(self::PIN_FAKE);
        $verification->setPin(self::PIN_FAKE)->shouldBeCalled();

        $verification->setSent(false)->shouldBeCalled();

        $phoneRepository->save($verification, true)->shouldBeCalled();

        $this->disableSmsTwoFactor($verification);
    }

    public function it_should_not_send_hotp_if_it_was_already_sent_5_times(User $user)
    {
        $user->getHotpSentTimes()->willReturn(5);

        $this->shouldThrow(new HotpSentTimesExceededException())->duringSendHotp($user);
    }

    public function it_should_sent_hotp_if_it_was_not_sent_before(
        SmsService $sms,
        User $user,
        PhoneVerification $verification,
        PhoneRepository $phoneRepository,
        UserRepository $userRepository
    ) {
        $user->getHotpSentTimes()->willReturn(0);
        $user->getHotpAuthCounter()->willReturn(0);
        $user->getHotpAuthKey()->willReturn('123456987');
        $user->setHotpSentTimes(1)->shouldBeCalled();

        $userRepository->save($user, true)->shouldBeCalled();

        $phoneRepository->findConfirmed($user)->willReturn($verification);

        $sms->send(self::PHONE_FAKE, Argument::any())->shouldBeCalled();

        $this->sendHotp($user);
    }

    public function it_is_inc_hotp_counter(User $user)
    {
        $user->hasHOTP()->willReturn(true);
        $user->getHotpAuthCounter()->willReturn(0);
        $user->setHotpAuthCounter(1)->shouldBeCalled();
        $user->setHotpSentTimes(0)->shouldBeCalled();

        $this->incHotpCounter($user);
    }
}
