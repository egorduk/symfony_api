<?php

namespace Btc\FrontendApiBundle\Repository;

use Btc\CoreBundle\Entity\User;
use Doctrine\ORM\EntityRepository;

class UserRepository extends EntityRepository
{
    public function findByUsernameOrEmail($username)
    {
        $query = $this->getEntityManager()
            ->createQuery('
                SELECT u FROM BtcCoreBundle:User u
                WHERE u.username = :username
                    OR u.email = :username
            ')->setParameters(compact('username'));

        $query->setMaxResults(1);

        return current($query->getResult());
    }

    public function save(User $object, $flush = false)
    {
        $this->getEntityManager()->persist($object);

        if ($flush === true) {
            $this->getEntityManager()->flush();
        }

        return $object;
    }

    public function remove(User $object, $flush = false)
    {
        $this->getEntityManager()->remove($object);

        if ($flush === true) {
            $this->getEntityManager()->flush();
        }

        return true;
    }
}
