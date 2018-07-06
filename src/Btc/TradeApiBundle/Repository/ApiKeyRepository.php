<?php

namespace Btc\TradeApiBundle\Repository;

use Btc\CoreBundle\Entity\ApiKey;
use Doctrine\ORM\EntityRepository;

class ApiKeyRepository extends EntityRepository
{
    public function findOneWithUserByKey($key)
    {
        return $this->createQueryBuilder('a')
            ->select('a')
            ->join('a.user', 'u')
            ->where('a.key = :Key')
            ->setParameter('Key', $key)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function save(ApiKey $object, $flush = false)
    {
        $this->_em->persist($object);

        if ($flush === true) {
            $this->_em->flush();
        }

        return $object;
    }
}
