<?php

namespace Btc\FrontendApiBundle\NsqMessage;

use Exmarkets\NsqBundle\Message\NsqMessage;
use Btc\CoreBundle\Entity\PriceNotification;

class PriceNotificationMessage extends NsqMessage
{
    public function __construct(PriceNotification $notification)
    {
        parent::__construct([
            'id' => intval($notification->getId()),
            'market' => (string) $notification->getMarket()->getSlug(),
            'price' => doubleval($notification->getPrice()),
            'email' => (string) $notification->getEmail(),
            'hash' => (string) $notification->getHash(),
            'current_price' => doubleval($notification->getCurrentPrice()),
            'timestamp' => intval($notification->getCreatedAt()->getTimestamp()),
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function isValid()
    {
        if (!preg_match('/[a-z]{3}-[a-z]{3}/', $this->data['market'])) {
            return false;
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function topic()
    {
        return 'new.price.notification';
    }
}
