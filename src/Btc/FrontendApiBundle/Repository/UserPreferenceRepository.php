<?php

namespace Btc\FrontendApiBundle\Repository;

use Btc\CoreBundle\Entity\User;
use Btc\CoreBundle\Entity\UserPreference;
use Doctrine\ORM\EntityRepository;

class UserPreferenceRepository extends EntityRepository
{
    /**
     * @param User   $user
     * @param string $preference
     * @param int    $value
     *
     * @return \Doctrine\DBAL\Driver\Statement
     */
    public function updateUserPreference(User $user, $preference, $value)
    {
        return $this->_em->getConnection()->executeUpdate(
            'UPDATE user_preference AS up
            INNER JOIN preference AS p ON p.id = up.preference_id
            SET up.value = :value WHERE up.user_id = :user AND p.slug =  :preference',
            ['user' => $user->getId(), 'preference' => $preference, 'value' => $value]
        );
    }

    /**
     * @param User   $user
     * @param string $preference
     *
     * @return int
     */
    public function getUserPreferenceValue(User $user, $preference)
    {
        $qb = $this->createQueryBuilder('up')
            ->innerJoin('up.preference', 'p')
            ->where('up.user = :user')
            ->andWhere('p.slug = :preference')
            ->select('up.value');

        $qb->setParameters(['user' => $user, 'preference' => $preference]);

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    public function save(UserPreference $object = null, $flush = false)
    {
        if ($object instanceof UserPreference) {
            $this->_em->persist($object);
        }

        if ($flush === true) {
            $this->_em->flush();
        }

        return $object;
    }
}
