<?php

namespace Btc\FrontendApiBundle\Repository;

use Btc\CoreBundle\Entity\User;
use Doctrine\ORM\EntityRepository;

class VoucherRepository extends EntityRepository
{
    public function getCreatedVouchersByUserQueryBuilder(User $user)
    {
        return $this->createQueryBuilder('v')
            ->select('v')
            ->where('v.createdByUser = :user')
            ->setParameter('user', $user)
            ->orderBy('v.createdAt', 'desc');
    }

    public function getRedeemedVouchersByUserQueryBuilder(User $user)
    {
        return $this->createQueryBuilder('v')
            ->select('v')
            ->where('v.redeemedByUser = :user')
            ->setParameter('user', $user)
            ->orderBy('v.createdAt', 'desc');
    }

    public function getRedeemedVouchersByUser(User $user)
    {
        return $this->getRedeemedVouchersByUserQueryBuilder($user)
            ->getQuery()
            ->getResult();
    }

    public function getCreatedVouchersQueryBuilder()
    {
        return $this->createQueryBuilder('v')
            ->select('v')
            ->orderBy('v.createdAt', 'desc');
    }
}
