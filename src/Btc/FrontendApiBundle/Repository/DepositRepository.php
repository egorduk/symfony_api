<?php

namespace Btc\FrontendApiBundle\Repository;

use Btc\CoreBundle\Entity\Currency;
use Btc\CoreBundle\Entity\Deposit\Deposit;
use Btc\CoreBundle\Entity\Transfer;
use Btc\CoreBundle\Entity\User;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\QueryBuilder;

class DepositRepository extends BaseDepositRepository
{
    public function getDepositsQueryBuilder(User $user)
    {
        return $this->createQueryBuilder('t')
            ->join('t.wallet', 'w')
            ->where('t.wallet IN (:wallets)')
            ->setParameter('wallets', $user->getWallets())
            ->orderBy('t.createdAt', 'desc');
    }

    public function excludeNewStatus(QueryBuilder $qb)
    {
        return $qb
            ->andWhere('t.status != :new')
            ->setParameter('new', Transfer::STATUS_NEW);
    }

    /**
     * @param User     $user
     * @param int      $days
     * @param string   $status
     * @param Currency $currency
     *
     * @return QueryBuilder
     */
    public function getUserDepositsQueryBuilder(User $user, $days, $status, $currency)
    {
        $qb = $this->createQueryBuilder('d')
            ->join('d.wallet', 'w')
            ->join('w.currency', 'c')
            ->where('d.wallet IN (:wallets)');

        $params = [
            'wallets' => $user->getWallets(),
            'currency' => $currency,
        ];

        is_array($currency) ?
            $qb->andWhere('w.currency IN (:currency)') :
            $qb->andWhere('w.currency = :currency');

        if ($days !== null) {
            $beginDate = (new \DateTime('+' . $days . ' days'))->format('Y-m-d 23:59:59');
            $endDate = (new \DateTime('-' . $days . ' days'))->format('Y-m-d 00:00:00');

            $qb->andWhere('d.createdAt <= :beginDate')
                ->andWhere('d.createdAt >= :endDate');

            $params = array_merge($params, [
                'beginDate' => $beginDate,
                'endDate' => $endDate,
            ]);
        }

        if ($status !== null) {
            $qb->andWhere('d.status = :status');

            $params = array_merge($params, ['status' => $status]);
        }

        $qb->setParameters($params)
            ->orderBy('d.createdAt', 'desc');

        return $qb;
    }

    /**
     * Returns unconfirmed wire user deposit
     *
     * @throws NoResultException when deposit was not found
     * @param User $user
     * @param int $deposit
     * @return mixed
     */
    public function getUserUnconfirmedWireDeposit(User $user, $deposit)
    {
        $qb = $this->getDepositsQueryBuilder($user)
            ->andWhere('t.id = :id')
            ->andWhere('t.status = :status')
            ->andWhere('t INSTANCE OF Btc\CoreBundle\Entity\WireDeposit')
            ->setParameter('id', $deposit)
            ->setParameter('status', 'new')
            ->setMaxResults(1);

        return $qb->getQuery()->getSingleResult();
    }

    public function findUserWireDeposit(User $user, $id)
    {
        $qb = $this->getDepositsQueryBuilder($user);
        $qb->andWhere('t.id = :id');
        $qb->andWhere('t INSTANCE OF Btc\CoreBundle\Entity\WireDeposit');
        $qb->andWhere($qb->expr()->in('t.status', ':status'));
        $qb->setParameter('id', $id);
        $qb->setParameter('status', [
            Deposit::STATUS_NEW,
            Deposit::STATUS_COMPLETED,
            Deposit::STATUS_TRANSFERRED,
            Deposit::STATUS_CANCELED,
        ]);

        return $qb->getQuery()->getSingleResult();
    }

    public function findUserInternationalDeposits(User $user)
    {
        $qb = $this->getDepositsQueryBuilder($user);
        $qb->join('t.bank', 'b');
        $qb->andWhere('b.slug = :slug')->setParameter('slug', 'international-wire-transfer');
        $qb->andWhere($qb->expr()->in('t.status', ':status'));
        $qb->setParameter('status', [
            Deposit::STATUS_NEW,
            Deposit::STATUS_TRANSFERRED,
        ]);
        return $qb->getQuery()->getResult();
    }

    public function save(Deposit $object, $flush = false)
    {
        $this->getEntityManager()->persist($object);

        if ($flush === true) {
            $this->getEntityManager()->flush();
        }

        return $object;
    }
}
