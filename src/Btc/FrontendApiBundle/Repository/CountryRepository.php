<?php

namespace Btc\FrontendApiBundle\Repository;

use Doctrine\ORM\EntityRepository;

class CountryRepository extends EntityRepository
{
    public function findAllChoosable()
    {
        return $this->createQueryBuilder('c')
            ->select('c')
            ->where('c.hidden = :hidden')
            ->orderBy('c.name', 'ASC')
            ->getQuery()
            ->setParameter('hidden', false)
            ->useResultCache(true, 3600)
            ->getResult();
    }
}
