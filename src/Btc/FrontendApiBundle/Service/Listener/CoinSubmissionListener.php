<?php

namespace Btc\FrontendApiBundle\Service\Listener;

use Btc\FrontendApiBundle\Events\CoinSubmissionEvent;
use Btc\FrontendApiBundle\Service\EmailSenderService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class CoinSubmissionListener implements EventSubscriberInterface
{
    private $mailer;

    public function __construct(EmailSenderService $mailer)
    {
        $this->mailer = $mailer;
    }

    /**
     * Returns an array of event names this subscriber wants to listen to.
     *
     * @return array The event names to listen to
     *
     * @api
     */
    public static function getSubscribedEvents()
    {
        return [
            CoinSubmissionEvent::EVENT => 'onCoinSubmit',
        ];
    }

    public function onCoinSubmit(CoinSubmissionEvent $event)
    {
        $this->mailer->sendCoinSubmissionEmail($event->getSubmission());
    }

}
