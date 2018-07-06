<?php

namespace Btc\FrontendApiBundle\Repository;

use Btc\CoreBundle\Entity\Activity;
use Btc\CoreBundle\Entity\User;
use Doctrine\ORM\EntityRepository;

class ActivityRepository extends EntityRepository
{
    public function getActivitiesQueryBuilder(User $user)
    {
        return $this->createQueryBuilder('a')
            ->select('a')
            ->where('a.user = :user')
            ->setParameter('user', $user)
            ->orderBy('a.createdAt', 'desc');
    }

    public function save(Activity $object, $flush = false)
    {
        $this->_em->persist($object);

        if ($flush === true) {
            $this->_em->flush();
        }

        return $object;
    }
}
