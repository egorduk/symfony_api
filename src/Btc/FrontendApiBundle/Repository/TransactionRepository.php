<?php

namespace Btc\FrontendApiBundle\Repository;

use Btc\CoreBundle\Entity\Order;
use Btc\CoreBundle\Entity\Transaction;
use Btc\CoreBundle\Entity\Market;
use Btc\CoreBundle\Entity\User;
use Doctrine\ORM\EntityRepository;

class TransactionRepository extends EntityRepository
{
    public function getTransactionsQueryBuilder()
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.status IN (:status)')
            ->setParameter('status', [Transaction::STATUS_COMPLETED])
            ->orderBy('t.completedAt', 'desc');
    }

    public function getLatestTransactionsByMarket(Market $market, $limit = null)
    {
        $qb = $this->createQueryBuilder('t')
            ->select('IDENTITY(t.deal) as deal_id, SUM(t.amount) as amount, t.price, UNIX_TIMESTAMP(t.executedAt) as time')
            ->where('t.market = :market')
            ->andWhere('t.status = :status')
            ->andWhere('t.amount > 0')
            ->addGroupBy('t.deal')
            ->addGroupBy('t.price')
            ->orderBy('t.executedAt', 'DESC');

        $qb->setParameters(['market' => $market, 'status' => Transaction::STATUS_COMPLETED]);
        $qb->setMaxResults($limit);

        return $qb->getQuery()->getArrayResult();
    }

    public function getLastAskedPriceByMarket(Market $market)
    {
        $lastTransaction = current($this->getLatestTransactionsByMarket($market, 1));

        return $lastTransaction['asked_unit_price'] ?: 0;
    }

    public function getYearlyTransactionPriceGroupedByDay(Market $market, $externalDataDir)
    {
        $cache = $this->_em->getConfiguration()->getQueryCacheImpl();
        $data = $cache->fetch('query.results.transactions.yearly_data.'.$market->getSlug());
        if ($data === false) {
            $lastYear = new \DateTime('-1 day');
            $lastYear->modify('-1 year');
            $sql = <<<'SQL'
SELECT
  DATE(t.completed_at) AS dt,
  SUBSTRING_INDEX(GROUP_CONCAT(t.price), ',', 1) as price
FROM transactions AS t
INNER JOIN orders AS d ON d.id = t.order_id
WHERE d.market_id = ?
  AND t.status = ?
  AND t.completed_at BETWEEN ? AND ?
GROUP BY dt
ORDER BY t.completed_at ASC
SQL;
            $now = strtotime('-1 day');
            $days = $this->getEntityManager()->getConnection()->fetchAll($sql, [
                $market->getId(),
                Transaction::STATUS_COMPLETED,
                $lastYear->format('Y-m-d H:i:s'),
                date('Y-m-d H:i:s', $now),
            ], [\PDO::PARAM_INT, \PDO::PARAM_INT, \PDO::PARAM_STR]);

            $data = [];
            // format from database, days which had transactions
            foreach ($days as $entry) {
                $data[$entry['dt']] = $entry['price'];
            }

            $data = $this->fillInExternalData($data, $externalDataDir, $market->getSlug());

            $lastDay = false;
            // go through all year and fill in gaps
            while ($now > $lastYear->getTimestamp()) {
                $day = date('Y-m-d', $lastYear->getTimestamp());
                if (!array_key_exists($day, $data)) {
                    // if there is no data, use last day open deal, otherwise 0
                    $data[$day] = $lastDay ? $data[$lastDay] : 0;
                }
                $lastYear->modify('+1 day');
                $lastDay = $day;
            }
            krsort($data);
            $cache->save('query.results.transactions.yearly_data.'.$market->getSlug(), $data, 24 * 3600);
        }

        return $data;
    }

    public function getUserTransactionsByOrder(User $user, Order $order)
    {
        $qb = $this->createQueryBuilder('t')
            ->select('t.id, t.executedAt, t.price, t.amount, t.fee')
            ->innerJoin('t.order', 'o')
            ->innerJoin('o.market', 'm')
            ->where('t.order = :id')
            ->andWhere('o.inWallet IN (:wallets)')
            ->setParameters([
                'id' => $order,
                'wallets', $user->getWallets(),
            ]);

        return $qb->getQuery()->getResult();
    }

    public function getUserTransactionsByMarket(User $user, Market $market)
    {
        $qb = $this->createQueryBuilder('t')
            ->select('t')
            ->innerJoin('t.order', 'o')
            ->innerJoin('t.market', 'm')
            ->where('m = :market')
            ->andWhere('o.inWallet IN (:wallets)')
            ->setParameters([
                'market' => $market,
                'wallets' => $user->getWallets(),
            ]);

        return $qb->getQuery()->getResult();
    }

    private function fillInExternalData(array $data, $externalDataDir, $slug)
    {
        $fileDir = "{$externalDataDir}/{$slug}.json";
        if (!file_exists($fileDir)) {
            return $data;
        }
        $externalData = json_decode(file_get_contents($fileDir), true);

        $exmOpenTime = new \DateTime('2014-06-23');
        $lastThreeYears = new \DateTime('-3 year');

        while ($lastThreeYears->getTimestamp() < $exmOpenTime->getTimestamp()) {
            $day = date('Y-m-d', $lastThreeYears->getTimestamp());
            if (!array_key_exists($day, $data)) {
                $data[$day] = $externalData[$day];
            }
            $lastThreeYears->modify('+1 day');
        }

        return $data;
    }

    /**
     * Find all transactions within $scope in $market
     *
     * @param string $scope - hour or minute
     * @param Market $market
     *
     * @return array
     */
    public function findAllWithinScopeInMarket($scope, Market $market)
    {
        $start = new \DateTime('-1 ' . $scope, new \DateTimeZone('UTC'));

        $qb = $this->createQueryBuilder('t')
            ->select('t.id, t.status, t.price, t.amount, t.completedAt, (CASE WHEN (t.amount > 0) THEN \'SELL\' ELSE \'BUY\' END) as side')
            ->innerJoin('t.market', 'm')
            ->where('t.market = :market')
            ->andWhere('t.type = :type')
            ->andWhere('t.status = :status')
            ->andWhere('t.completedAt <= :completedAt')
            ->setParameters([
                'market' => $market,
                'type' => Transaction::TYPE_TAKER,
                'status' => Transaction::STATUS_COMPLETED,
                'completedAt' => $start,
            ]);

        return $qb->getQuery()->getResult();
    }
}
