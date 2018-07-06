<?php

namespace Btc\FrontendApiBundle\Repository;

use Doctrine\ORM\EntityRepository;

class PageRepository extends EntityRepository
{
    public function findPage($path)
    {
        $path = rtrim($path, '/');

        return $this
            ->createQueryBuilder('p')
            ->select('p')
            ->where('p.path =:path')
            ->setParameters(compact('path'))
            ->getQuery()
            ->useResultCache(true, 3600)
            ->getOneOrNullResult();
    }
}
