<?php

namespace Btc\FrontendApiBundle\Service;

use Btc\FrontendApiBundle\Exception\Rest\SmsSendingException;

class SmsService
{
    private $sender;
    private $from;

    public function __construct(\Services_Twilio $sender, $from)
    {
        $this->sender = $sender;
        $this->from = $from;
    }

    /**
     * @param $to
     * @param $message
     *
     * @throws SmsSendingException
     *
     * @return bool
     */
    public function send($to, $message)
    {
        try {
            $this->sender->account->sms_messages->create(
                $this->from, $to, $message, []
            );
        } catch (\Services_Twilio_RestException $e) {
            switch ($e->getCode()) {
                case 21211:
                    throw new SmsSendingException('sms.sending.invalid_phone', null, $e);
                case 21612:
                    throw new SmsSendingException('sms.sending.no_route', null, $e);
                case 21408:
                    throw new SmsSendingException('sms.sending.cant_route', null, $e);
                case 21610:
                    throw new SmsSendingException('sms.sending.blacklisted', null, $e);
                case 21614:
                    throw new SmsSendingException('sms.sending.target_cant_receive', null, $e);
                default:
                    throw new SmsSendingException('sms.sending.failed', null, $e);
            }
        }

        return true;
    }
}
