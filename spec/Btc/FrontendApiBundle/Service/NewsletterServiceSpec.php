<?php

namespace spec\Btc\FrontendApiBundle\Service;

use Btc\CoreBundle\Entity\User;
use Btc\FrontendApiBundle\Repository\UserPreferenceRepository;
use Btc\FrontendApiBundle\Service\NewsletterService;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Exmarkets\NsqBundle\Message\NewsletterSubscriptionMessage;
use Exmarkets\NsqBundle\Nsq;

class NewsletterServiceSpec extends ObjectBehavior
{
    const MAILING_LIST_EMAIL_FAKE = 'test1@test.test';
    const USER_EMAIL_FAKE = 'test@test.test';
    const USER_NAME_FAKE = 'test';

    public function let(
        UserPreferenceRepository $preferenceRepository,
        Nsq $nsq,
        User $user
    ) {
        $user->getEmail()->willReturn(self::USER_EMAIL_FAKE);
        $user->__toString()->willReturn(self::USER_NAME_FAKE);

        $this->beConstructedWith($preferenceRepository, $nsq, self::MAILING_LIST_EMAIL_FAKE);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(NewsletterService::class);
    }

    public function it_unsubscribes_from_mailing_list_and_updates_preference(
        Nsq $nsq,
        UserPreferenceRepository $preferenceRepository,
        User $user
    ) {
        $preferenceRepository->updateUserPreference($user, NewsletterService::NEWSLETTER_PREFERENCE_SLUG, 0)
            ->shouldBeCalled();

        $nsq->send(
            Argument::that(
                function (NewsletterSubscriptionMessage $msg) {
                    $msg = json_decode($msg->payload(), true);

                    return $msg['email'] === self::USER_EMAIL_FAKE &&
                    $msg['full_name'] === self::USER_NAME_FAKE &&
                    $msg['subscribed'] === false &&
                    $msg['mailing_list'] === self::MAILING_LIST_EMAIL_FAKE;
                }
            )
        )->shouldBeCalled();

        $this->unsubscribe($user);
    }

    public function it_subscribes_user_to_mailing_list_and_updates_preference(
        Nsq $nsq,
        UserPreferenceRepository $preferenceRepository,
        User $user
    ) {
        $preferenceRepository->updateUserPreference($user, NewsletterService::NEWSLETTER_PREFERENCE_SLUG, 1)
            ->shouldBeCalled();

        $nsq->send(
            Argument::that(
                function (NewsletterSubscriptionMessage $msg) {
                    $msg = json_decode($msg->payload(), true);

                    return $msg['email'] === self::USER_EMAIL_FAKE &&
                    $msg['full_name'] === self::USER_NAME_FAKE &&
                    $msg['subscribed'] === true &&
                    $msg['mailing_list'] === self::MAILING_LIST_EMAIL_FAKE;
                }
            )
        )->shouldBeCalled();

        $this->subscribe($user);
    }

    public function it_should_update_user_mailing_list_subscription_by_current_unsubscribed_preference(
        Nsq $nsq,
        UserPreferenceRepository $preferenceRepository,
        User $user
    ) {
        $preferenceRepository->getUserPreferenceValue($user, NewsletterService::NEWSLETTER_PREFERENCE_SLUG)
            ->shouldBeCalled()->willReturn(0);

        $nsq->send(
            Argument::that(
                function (NewsletterSubscriptionMessage $msg) {
                    $msg = json_decode($msg->payload(), true);

                    return $msg['email'] === self::USER_EMAIL_FAKE &&
                    $msg['full_name'] === self::USER_NAME_FAKE &&
                    $msg['subscribed'] === false &&
                    $msg['mailing_list'] === self::MAILING_LIST_EMAIL_FAKE;
                }
            )
        )->shouldBeCalled();

        $this->updateSubscriptionByPreference($user);
    }

    public function it_should_update_user_mailing_list_subscription_by_current_subscribed_preference(
        Nsq $nsq,
        UserPreferenceRepository $preferenceRepository,
        User $user
    ) {
        $preferenceRepository->getUserPreferenceValue($user, NewsletterService::NEWSLETTER_PREFERENCE_SLUG)
            ->shouldBeCalled()->willReturn(1);

        $nsq->send(
            Argument::that(
                function (NewsletterSubscriptionMessage $msg) {
                    $msg = json_decode($msg->payload(), true);

                    return $msg['email'] === self::USER_EMAIL_FAKE &&
                    $msg['full_name'] === self::USER_NAME_FAKE &&
                    $msg['subscribed'] === true &&
                    $msg['mailing_list'] === self::MAILING_LIST_EMAIL_FAKE;
                }
            )
        )->shouldBeCalled();

        $this->updateSubscriptionByPreference($user);
    }
}
