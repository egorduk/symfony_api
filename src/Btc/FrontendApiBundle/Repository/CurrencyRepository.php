<?php

namespace Btc\FrontendApiBundle\Repository;

use Doctrine\ORM\EntityRepository;

class CurrencyRepository extends EntityRepository
{
    public function getUsdEurCurrencies()
    {
        $currencies = ['EUR', 'USD'];

        return $this->createQueryBuilder('t')
            ->where('t.code in (:currencies)')
            ->setParameter('currencies', $currencies)
            ->getQuery()
            ->getResult();
    }

    public function getVirtualCurrencies()
    {
        return $this->findBy(['crypto' => true]);
    }
}
