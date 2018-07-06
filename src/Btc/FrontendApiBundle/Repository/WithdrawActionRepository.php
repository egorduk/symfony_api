<?php

namespace Btc\FrontendApiBundle\Repository;

use Btc\CoreBundle\Entity\Currency;
use Doctrine\ORM\EntityRepository;

class WithdrawActionRepository extends EntityRepository
{
    public function findLastWithdrawals(Currency $currency, $limit = 25)
    {
        $qb = $this->createQueryBuilder('w');
        $qb->join('w.currency', 'c', '')
            ->join('w.user', 'u')
            ->where('w.currency = :currency')
            ->orderBy('w.createdAt', 'DESC');

        $qb->setParameter('currency', $currency);
        $qb->setMaxResults($limit);

        return $qb->getQuery()->getResult();
    }

    public function findAllWithdrawals(Currency $currency)
    {
        $qb = $this->createQueryBuilder('w');
        $qb->join('w.currency', 'c', '')
            ->join('w.user', 'u')
            ->where('w.currency = :currency')
            ->orderBy('w.createdAt', 'DESC')
            ->setParameter('currency', $currency);

        return $qb->getQuery()->getResult();
    }
} 
