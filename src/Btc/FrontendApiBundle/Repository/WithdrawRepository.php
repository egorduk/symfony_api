<?php

namespace Btc\FrontendApiBundle\Repository;

use Btc\CoreBundle\Entity\Currency;
use Btc\CoreBundle\Entity\Transfer;
use Btc\CoreBundle\Entity\User;
use Btc\CoreBundle\Entity\Withdraw\Withdraw;
use Doctrine\ORM\QueryBuilder;

class WithdrawRepository extends BaseWithdrawRepository
{
    public function excludeNewStatus(QueryBuilder $qb)
    {
        $qb->andWhere('t.status != :new')
            ->setParameter('new', Transfer::STATUS_NEW);

        return $qb;
    }

    /**
     * @param User $user
     * @param int $days
     * @param string $status
     * @param Currency $currency
     *
     * @return QueryBuilder
     */
    public function getUserWithdrawsQueryBuilder(User $user, $days, $status, $currency)
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

    public function save(Withdraw $object, $flush = false)
    {
        $this->getEntityManager()->persist($object);

        if ($flush === true) {
            $this->getEntityManager()->flush();
        }

        return $object;
    }
}
