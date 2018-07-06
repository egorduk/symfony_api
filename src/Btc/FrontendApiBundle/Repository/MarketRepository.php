<?php

namespace Btc\FrontendApiBundle\Repository;

use Btc\CoreBundle\Entity\Market;
use Btc\CoreBundle\Entity\Order;
use Doctrine\ORM\EntityRepository;

class MarketRepository extends EntityRepository
{
    public function findAllForTrading()
    {
        $qb = $this->createQueryBuilder('m')->select('m');

        return $qb->where($qb->expr()->eq('m.internal', ':internal'))
            ->orderBy('m.withCurrency')
            ->getQuery()
            ->setParameter('internal', false)
            ->useResultCache(true, 24 * 3600, 'query.results.markets.non_internal')
            ->getResult();
    }

    /**
     * Fetches highest bid.
     *
     * @param Market $market
     *
     * @return bool|\Btc\CoreBundle\Entity\Order
     */
    public function getHighestBid(Market $market)
    {
        $statuses = [ORDER::STATUS_OPEN];
        $side = ORDER::SIDE_BUY;
        $qb = $this->_em->createQueryBuilder()
            ->select('s')
            ->from('BtcCoreBundle:Order', 's')
            ->orderBy('s.askedUnitPrice', 'DESC')
            ->where('s.market = :market')
            ->andWhere('s.status IN (:statuses)')
            ->andWhere('s.side = :side')
            ->setParameters(compact('statuses', 'market', 'side'))
            ->setMaxResults(1);

        return current($qb->getQuery()->getResult());
    }

    /**
     * Fetches the cheapest sell deal depending on market.
     *
     * If there are no deals it will return false
     *
     * @param \Btc\CoreBundle\Entity\Market $market
     *
     * @return bool|\Btc\CoreBundle\Entity\Order
     */
    public function getLowestAsk(Market $market)
    {
        $statuses = [ORDER::STATUS_OPEN];
        $side = ORDER::SIDE_SELL;
        $qb = $this->_em->createQueryBuilder()
            ->select('s')
            ->from('BtcCoreBundle:Order', 's')
            ->orderBy('s.askedUnitPrice')
            ->where('s.market = :market')
            ->andWhere('s.status IN (:statuses)')
            ->andWhere('s.side = :side')
            ->setParameters(compact('statuses', 'market', 'side'))
            ->setMaxResults(1);

        return current($qb->getQuery()->getResult());
    }

    /**
     * Proxy for phpspec.
     */
    public function findOneBySlug($slug)
    {
        $qb = $this->createQueryBuilder('m')->select('m, fiat, virt');

        return $qb->where($qb->expr()->eq('m.slug', ':slug'))
            ->join('m.currency', 'virt')
            ->join('m.withCurrency', 'fiat')
            ->setParameters(compact('slug'))
            ->getQuery()
            ->useResultCache(true)
            ->getSingleResult();
    }
}
