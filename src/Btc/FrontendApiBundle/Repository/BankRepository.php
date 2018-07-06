<?php

namespace Btc\FrontendApiBundle\Repository;

use Doctrine\ORM\EntityRepository;

class BankRepository extends EntityRepository
{
    public function findOneBySlugFiat($slug)
    {
        return $this->findOneBy(['slug' => $slug, 'fiat' => true]);
    }

    public function findOneBySlugVirtual($slug)
    {
        return $this->findOneBy(['slug' => $slug, 'fiat' => false]);
    }

    public function getAvailableFiatBanksToDeposit()
    {
        $qb = $this->getFiatBanksByMethodsQueryBuilder('deposit');

        return $qb->getQuery()->getResult();
    }

    public function getAvailableFiatBanksToWithdraw()
    {
        $qb = $this->getFiatBanksByMethodsQueryBuilder('withdrawal');

        return $qb->getQuery()->getResult();
    }

    /**
     * @param string $type deposit or withdrawal
     *
     * @return \Doctrine\ORM\QueryBuilder
     */
    private function getFiatBanksByMethodsQueryBuilder($type)
    {
        $qb = $this->createQueryBuilder('b');
        $qb->where($qb->expr()->eq('b.fiat', true))
            ->andWhere($qb->expr()->eq("b.{$type}Available", true));

        return $qb;
    }
}
