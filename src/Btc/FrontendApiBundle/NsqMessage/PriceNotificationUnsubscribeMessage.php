<?php

namespace Btc\FrontendApiBundle\NsqMessage;

use Exmarkets\NsqBundle\Message\NsqMessage;

class PriceNotificationUnsubscribeMessage extends NsqMessage
{
    public function __construct($email)
    {
        parent::__construct([
            'email' => (string) $email,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function isValid()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function topic()
    {
        return 'unsubscribe.price.notification';
    }
}
