<?php

namespace Btc\FrontendApiBundle\Repository;

use Btc\CoreBundle\Entity\Market;
use Btc\CoreBundle\Entity\Wallet;
use Btc\CoreBundle\Entity\User;
use Btc\CoreBundle\Entity\Currency;
use Doctrine\ORM\EntityRepository;

class WalletRepository extends EntityRepository
{
    /**
     * TODO: fix later
     *
     * @param User     $user
     * @param Currency $currency
     *
     * @return Wallet
     */
    public function findOneForUserAndCurrency(User $user, Currency $currency)
    {
        $query = $this->getEntityManager()
            ->createQuery('
                SELECT w FROM BtcCoreBundle:Wallet w
                WHERE w.user = :user
                AND w.currency = :currency
            ')
            ->setParameters(compact('user', 'currency'))
            ->setMaxResults(1);

        return current($query->getResult());
    }

    public function save(Wallet $object, $flush = false)
    {
        $this->getEntityManager()->persist($object);

        if ($flush === true) {
            $this->getEntityManager()->flush();
        }

        return $object;
    }

    public function findAllUserWalletsWithinMarket(User $user, Market $market)
    {
        return $this->createQueryBuilder('w')
            ->select('w')
            ->where('w.user = (:user)')
            ->andWhere('w.currency IN (:currencies)')
            ->setParameters([
                'user' => $user,
                'currencies' => [
                    $market->getCurrency()->getId(),
                    $market->getWithCurrency()->getId(),
                ]
            ])
            ->getQuery()
            ->getResult();
    }
}
