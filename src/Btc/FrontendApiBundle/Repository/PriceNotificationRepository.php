<?php

namespace Btc\FrontendApiBundle\Repository;

use Btc\CoreBundle\Entity\PriceNotification;
use Doctrine\ORM\EntityRepository;

class PriceNotificationRepository extends EntityRepository
{
    public function findEmailByHash($hash)
    {
        $query = $this->getEntityManager()
            ->createQuery('
                SELECT p.email FROM BtcCoreBundle:PriceNotification p
                WHERE p.hash = :hash
            ')->setParameters(compact('hash'));

        $query->setMaxResults(1);

        return current($query->getResult()) ? current($query->getResult())['email'] : '';
    }

    public function cancelSubscriptionsForEmail($email)
    {
        return $this->getEntityManager()
            ->createQuery('UPDATE BtcCoreBundle:PriceNotification p
            SET p.status = :cancelStatus
            WHERE p.email = :email
            AND p.status = :status'
            )->setParameters([
                    'cancelStatus' => PriceNotification::STATUS_CANCELED,
                    'email' => $email,
                    'status' => PriceNotification::STATUS_OPEN,
                ])->execute();
    }
}
