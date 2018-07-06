<?php

namespace spec\Btc\FrontendApiBundle\Service;

use Btc\CoreBundle\Entity\EmailTemplate;
use Btc\CoreBundle\Entity\Settings;
use Btc\CoreBundle\Entity\User;
use Btc\CoreBundle\Entity\Verification;
use Btc\FrontendApiBundle\Repository\EmailTemplateRepository;
use Btc\FrontendApiBundle\Repository\SettingsRepository;
use Btc\FrontendApiBundle\Service\EmailSenderService;
use Exmarkets\NsqBundle\Nsq;
use Exmarkets\NsqBundle\Message\Email\HighPriorityEmailMessage;
use Knp\Bundle\MarkdownBundle\Parser\MarkdownParser;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Psr\Log\LoggerInterface;

class EmailSenderServiceSpec extends ObjectBehavior
{
    const BODY_FAKE = 'email html body';
    const FROM_FAKE = 'from';
    const TO_FAKE = 'email@address.com';
    const SUBJECT_FAKE = 'subject';
    const EMAIL_FAKE = 'email@address.com';

    public function let(
        Nsq $nsq,
        \Twig_Environment $twig,
        EmailTemplateRepository $templates,
        EmailTemplate $template,
        User $user,
        MarkdownParser $md,
        LoggerInterface $logger,
        SettingsRepository $settingsRepository,
        \Twig_Template $twig_Template
    ) {
        $this->beConstructedWith($nsq, $md, $twig, $templates, self::FROM_FAKE, $logger, $settingsRepository, 'host', 'token');

        $templates->findOneByName(Argument::any())->willReturn($template);

        $md->transform('markdown body')->willReturn('html');

        $twig_Template->render(Argument::any())->willReturn(self::BODY_FAKE);

        $twig->createTemplate(Argument::any())->willReturn($twig_Template);
        $twig->mergeGlobals(Argument::any())->will(function ($args) {
            return $args[0];
        });
        $twig->render('BtcCoreBundle:EmailLayout:exmarkets.html.twig', ['body' => self::BODY_FAKE])->willReturn(self::BODY_FAKE);

        $template->getBody()->willReturn('markdown body');
        $template->getSubject()->willReturn(self::SUBJECT_FAKE);

        $user->getEmail()->willReturn('user@email.com');
        $user->__toString()->willReturn('Exmarkets User');
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(EmailSenderService::class);
    }

   /* public function it_should_send_credentials_email_message(
        $user,
        $templates,
        $template,
        $twig,
        $nsq
    ) {
        $templates->findOneByName('user.register')->shouldBeCalled()->willReturn($template);
        $twig->render('html', compact('user'))->shouldBeCalled()->willReturn('html body');

        $nsq->send(Argument::that(function (HighPriorityEmailMessage $msg) {
            $msg = json_decode($msg->payload(), true);

            return $msg['from'] === 'from' && $msg['to'] === 'Exmarkets User <user@email.com>'
                && $msg['body'] === 'email html body' && $msg['subject'] === 'subject';
        }))->shouldBeCalled();

        $this->sendCredentialsEmailMessage($user, []);
    }*/

    public function it_should_send_pin_email_message(User $user, EmailTemplateRepository $templates, EmailTemplate $template, Nsq $nsq)
    {
        $templates->findOneByName('user.new_pin')->shouldBeCalled()->willReturn($template);

        $nsq->send(new HighPriorityEmailMessage(self::FROM_FAKE, 'Exmarkets User <user@email.com>', self::SUBJECT_FAKE, self::BODY_FAKE))->shouldBeCalled();

        $this->sendNewPinMessage($user, []);
    }

    public function it_should_send_verification_email_message(
        Verification $verification,
        EmailTemplateRepository $templates,
        EmailTemplate $template,
        Nsq $nsq,
        SettingsRepository $settingsRepository,
        Settings $settings
    ) {
        $templates->findOneByName('verification.notification')->shouldBeCalled()->willReturn($template);

        $settingsRepository->findOneBySlug('verification-email-notification')->willReturn($settings)->shouldBeCalled();

        $settings->getValue()->willReturn(self::TO_FAKE);

        $nsq->send(new HighPriorityEmailMessage(self::FROM_FAKE, self::TO_FAKE, self::SUBJECT_FAKE, self::BODY_FAKE))->shouldBeCalled();

        $this->sendVerificationNotificationEmail($verification, []);
    }
}
