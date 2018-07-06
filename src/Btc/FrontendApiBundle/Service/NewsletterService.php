<?php

namespace Btc\FrontendApiBundle\Service;

use Btc\CoreBundle\Entity\User;
use Btc\FrontendApiBundle\Repository\UserPreferenceRepository;
use Exmarkets\NsqBundle\Message\NewsletterSubscriptionMessage;
use Exmarkets\NsqBundle\Nsq;

class NewsletterService
{
    /**
     * Newsletter preference setting slug in database.
     */
    const NEWSLETTER_PREFERENCE_SLUG = 'preference.newsletter';

    /**
     * job queue.
     */
    private $nsq;

    /**
     * @var string Mailing list email
     */
    private $mailingListEmail;

    private $userPreferenceRepository;

    public function __construct(
        UserPreferenceRepository $userPreferenceRepository,
        Nsq $nsq,
        $mailingListEmail
    ) {
        $this->nsq = $nsq;
        $this->userPreferenceRepository = $userPreferenceRepository;
        $this->mailingListEmail = $mailingListEmail;
    }

    public function updateSubscriptionByPreference(User $user)
    {
        $msg = new NewsletterSubscriptionMessage(
            $user->getEmail(),
            $user->__toString(),
            $this->isNewsletterEnabled($user),
            $this->mailingListEmail
        );
        $this->nsq->send($msg);
    }

    /**
     * Enables user preference for newsletter and adds to the mailing list.
     *
     * @param User $user
     */
    public function subscribe(User $user)
    {
        $this->userPreferenceRepository->updateUserPreference($user, self::NEWSLETTER_PREFERENCE_SLUG, 1);
        $msg = new NewsletterSubscriptionMessage($user->getEmail(), $user->__toString(), true, $this->mailingListEmail);
        $this->nsq->send($msg);
    }

    /**
     * Disables user preference for newsletter and remove from the mailing list.
     *
     * @param User $user
     */
    public function unsubscribe(User $user)
    {
        $this->userPreferenceRepository->updateUserPreference($user, self::NEWSLETTER_PREFERENCE_SLUG, 0);
        $msg = new NewsletterSubscriptionMessage($user->getEmail(), $user->__toString(), false, $this->mailingListEmail);
        $this->nsq->send($msg);
    }

    /**
     * @param User $user
     *
     * @return bool
     */
    protected function isNewsletterEnabled(User $user)
    {
        return (bool) $this->userPreferenceRepository
            ->getUserPreferenceValue($user, self::NEWSLETTER_PREFERENCE_SLUG);
    }
}
