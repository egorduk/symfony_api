<?php

namespace spec\Btc\UserBundle\EventListener;

use Btc\UserBundle\Events\AccountActivityEvents;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class EmailCredentialsListenerSpec extends ObjectBehavior
{
    public function getMatchers()
    {
        return [
            'haveEvent' => function ($subject, $value) {
                return array_key_exists($value, $subject);
            },
        ];
    }

    /**
     * @param \Btc\UserBundle\Events\UserActivityEvent          $event
     * @param \Btc\FrontendApiBundle\Service\EmailSenderService $mailer
     * @param \Btc\CoreBundle\Entity\User                       $user
     */
    public function let(
        $event,
        $mailer,
        $user
    ) {
        $event->getUser()->willReturn($user);

        $this->beConstructedWith($mailer);
    }

    public function it_is_an_event_subscriber()
    {
        $this->shouldHaveType('Symfony\Component\EventDispatcher\EventSubscriberInterface');
    }

    public function it_should_subscribe_to_registration_completed_event()
    {
        $this->getSubscribedEvents()->shouldHaveEvent(AccountActivityEvents::REGISTRATION_COMPLETED);
    }

    public function it_should_send_email_with_credentials_on_event($event, $mailer)
    {
        $mailer
            ->sendCredentialsEmailMessage(Argument::type('Btc\CoreBundle\Entity\User'))
            ->shouldBeCalled();

        $this->onRegistrationComplete($event);
    }
}
