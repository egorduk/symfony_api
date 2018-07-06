<?php

namespace Btc\FrontendApiBundle\Service;

use Btc\Component\Market\Model\Voucher;
use Btc\CoreBundle\Entity\Bank;
use Btc\CoreBundle\Entity\Currency;
use Btc\CoreBundle\Entity\Verification;
use Btc\FrontendApiBundle\Repository\EmailTemplateRepository;
use Btc\FrontendApiBundle\Repository\SettingsRepository;
use Btc\CoreBundle\Entity\User;
use Btc\FrontendApiBundle\Entity\CoinSubmit;
use Exmarkets\NsqBundle\Message\NsqMessage;
use Exmarkets\NsqBundle\Nsq;
use Exmarkets\NsqBundle\Message\Email\HighPriorityEmailMessage;
use Exmarkets\PaymentCoreBundle\Gateway\Model\DepositModel;
use Exmarkets\PaymentCoreBundle\Gateway\Model\WithdrawModel;
use Knp\Bundle\MarkdownBundle\Parser\MarkdownParser;
use Nsq\Exception\PubException;
use Psr\Log\LoggerInterface;

class EmailSenderService
{
    private $from;
    private $fromName;
    private $templates;
    private $twig;
    private $markdown;
    private $nsq;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var \Btc\FrontendApiBundle\Repository\SettingsRepository
     */
    private $settings;

    private $host;
    private $tokenEmail;

    public function __construct(
        Nsq $nsq,
        MarkdownParser $md,
        \Twig_Environment $twig,
        EmailTemplateRepository $templates,
        $fromName,
        $fromEmail,
        LoggerInterface $logger,
        SettingsRepository $settings,
        $host,
        $tokenEmail
    ) {
        $this->from = $fromName.'<'.$fromEmail.'>';
        $this->fromName = $fromName;
        $this->nsq = $nsq;
        $this->twig = $twig;
        $this->templates = $templates;
        $this->markdown = $md;
        $this->logger = $logger;
        $this->settings = $settings;
        $this->host = $host;
        $this->tokenEmail = $tokenEmail;
    }

    /**
     * @param User  $user
     * @param array $vars additional vars to pass in when rendering template
     */
    public function sendNewPinMessage(User $user, array $vars = [])
    {
        $template = $this->templates->findOneByName('user.new_pin');
        $vars = array_merge(compact('user'), $vars, [
            'encoded_email' => urlencode($user->getEmail()),
            'host' => $this->host,
        ]);

        $msg = new HighPriorityEmailMessage(
            $this->from,
            sprintf('%s <%s>', trim((string) $user), $user->getEmail()),
            $template->getSubject(),
            $this->renderEmailBody($template->getBody(), $vars)
        );

        $this->sendMail($msg);
    }

    public function sendWithdrawNotificationEmail(WithdrawModel $withdrawal, Bank $bank, Currency $currency, array $vars = [])
    {
        $template = $this->templates->findOneByName('withdrawal.notification');
        $email = $this->settings->findOneBySlug('withdrawal-email-notification')->getValue();
        $vars = array_merge(compact('withdrawal', 'bank', 'currency'), $vars);
        $msg = new HighPriorityEmailMessage(
            $this->from,
            $email,
            $template->getSubject(),
            $this->renderEmailBody($template->getBody(), $vars)
        );

        $this->sendMail($msg);
    }

    public function sendDepositNotificationEmail(DepositModel $deposit, Bank $bank, Currency $currency, array $vars = [])
    {
        $template = $this->templates->findOneByName('deposit.notification');
        $email = $this->settings->findOneBySlug('deposit-email-notification')->getValue();
        $vars = array_merge(compact('deposit', 'bank', 'currency'), $vars);
        $msg = new HighPriorityEmailMessage(
            $this->from,
            $email,
            $template->getSubject(),
            $this->renderEmailBody($template->getBody(), $vars)
        );

        $this->sendMail($msg);
    }

    public function sendVerificationNotificationEmail(Verification $verification, array $vars = [])
    {
        $template = $this->templates->findOneByName('verification.notification');
        $email = $this->settings->findOneBySlug('verification-email-notification')->getValue();
        $vars = array_merge(compact('verification'), $vars);

        $msg = new HighPriorityEmailMessage(
            $this->from,
            $email,
            $template->getSubject(),
            $this->renderEmailBody($template->getBody(), $vars)
        );

        $this->sendMail($msg);
    }

    public function sendVoucherIssueNotificationEmail(Voucher $voucher, array $vars = [])
    {
        $template = $this->templates->findOneByName('voucher.issue');
        $vars = array_merge(compact('voucher'), $vars);

        $msg = new HighPriorityEmailMessage(
            $this->from,
            $voucher->getIssuer()->getEmail(),
            $template->getSubject(),
            $this->renderEmailBody($template->getBody(), $vars)
        );

        $this->sendMail($msg);
    }

    public function sendVoucherRedeemNotificationEmail(Voucher $voucher, array $vars = [])
    {
        $template = $this->templates->findOneByName('voucher.redeem');
        $vars = array_merge(compact('voucher'), $vars, [
            'redeemer' => $voucher->getRedeemer()->getEmail(),
        ]);

        $msg = new HighPriorityEmailMessage(
            $this->from,
            $voucher->getIssuer()->getEmail(),
            $template->getSubject(),
            $this->renderEmailBody($template->getBody(), $vars)
        );

        $this->sendMail($msg);
    }

    /**
     * @param NsqMessage $msg
     */
    private function sendMail(NsqMessage $msg)
    {
        try {
            $this->nsq->send($msg);
        } catch (PubException $e) {
            $this->logger->info('Failed sending notification');
            $this->logger->error($e);
        }
    }

    /**
     * Renders $markdownBody into html email layout.
     *
     * @param string $markdownBody
     * @param array  $vars
     *
     * @return string - email html body
     */
    private function renderEmailBody($markdownBody, array $vars)
    {
        $vars = $this->twig->mergeGlobals($vars);
        $template = $this->twig->createTemplate($this->markdown->transform($markdownBody));
        $body = $template->render($vars);

        return $this->twig->render('BtcCoreBundle:EmailLayout:exmarkets.html.twig', compact('body'));
    }


    public function sendCoinSubmissionEmail(CoinSubmit $submission) {
        $template = "This is an automated message. Please do not reply to this e-mail!
        
A new coin has requested listing on the exchanger. Details of the submission:

Project name and link to the website: {{ s.projectLink }}

Token name: {{ s.tokenName }}
 
Ticker symbol: {{ s.tokenTicker }}
 
Blockchain: {{ s.blockchain }}
  
Reddit and Bitcointalk threads: {{ s.threads }}
  
Team representative name: {{ s.repName }}
  
Team representative position: {{ s.repPosition }}
  
Team representative email: {{ s.repEmail }}
{% if s.hasICO %}

ICO token details

Token sale start date: {{ s.saleStart }}

Token sale end date: {{ s.saleEnd }}

Token sale start time: {{ s.saleStartTime }}

Token sale end time: {{ s.saleEndTime }}

Total token supply: {{ s.tokenTotal }}

ICO token price: {{ s.tokenPrice }}
{% endif %}
";

        $data = [
            'projectLink' => $submission->getProjectLink(),
            'tokenName' => $submission->getTokenName(),
            'tokenTicker' => $submission->getTokenTicker(),
            'blockchain' => $submission->getBlockchain(),
            'threads' => $submission->getSocialThreads(),
            'repName' => $submission->getRepresentativeName(),
            'repPosition' => $submission->getRepresentativePosition(),
            'repEmail' => $submission->getRepresentativeEmail(),

            'hasICO' => $submission->getisListingToken(),
            'saleStart' => $submission->getSaleStart(),
            'saleEnd' => $submission->getSaleEnd(),
            'saleStartTime' => $submission->getSaleStartTime(),
            'saleEndTime' => $submission->getSaleEndTime(),
            'tokenTotal' => $submission->getTokenSupply(),
            'tokenPrice' => $submission->getIcoTokenPrice(),

        ];

        $body = $this->renderEmailBody($template, ['s' => $data]);
        $msg = new HighPriorityEmailMessage(
            $this->from,
            $this->tokenEmail,
            "New coin submission",
            $body

        );

        $this->sendMail($msg);
    }
}
