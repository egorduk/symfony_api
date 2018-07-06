<?php

namespace Btc\FrontendApiBundle\Repository;

use Btc\CoreBundle\Entity\Market;
use Btc\CoreBundle\Entity\Order;
use Btc\CoreBundle\Entity\User;
use Btc\CoreBundle\Entity\Wallet;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr;

class OrderRepository extends EntityRepository
{
    public function getUserOpenDeal($id, User $user)
    {
        $wallets = $user->getWallets()->toArray();
        $statuses = [Order::STATUS_OPEN];
        $qb = $this->createQueryBuilder('d')
            ->select('d')
            ->where('d.id = :id')
            ->andWhere('d.inWallet IN (:wallets)')
            ->andWhere('d.status IN (:statuses)')
            ->setParameters(compact('wallets', 'statuses', 'id', 'types'));

        return current($qb->getQuery()->getResult());
    }

    public function getUserOpenDealsQueryBuilder(User $user)
    {
        $wallets = $user->getWallets()->toArray();
        $statuses = [Order::STATUS_OPEN];
        $qb = $this->createQueryBuilder('d')
            ->select('d, tt, m, fiat')
            ->join('d.market', 'm')
            ->join('m.withCurrency', 'fiat')
            ->leftJoin('d.transactions', 'tt')
            ->where('d.inWallet IN (:wallets)')
            ->andWhere('d.status IN (:statuses)')
            ->orderBy('d.createdAt', 'ASC')
            ->setParameters(['wallets' => $wallets, 'statuses' => $statuses]);

        return $qb;
    }

    public function getUserCompletedTransactionsBaseQueryBuilder(User $user)
    {
        $wallets = $user->getWallets()->toArray();
        $statuses = [Order::STATUS_COMPLETED, Order::STATUS_CANCELLED];
        $qb = $this->createQueryBuilder('d')
            ->select('d')
            ->join('d.market', 'm')
            ->where('d.inWallet IN (:wallets)')
            ->andWhere('d.status IN (:statuses)')
            ->orderBy('d.updatedAt', 'desc')
            ->setParameters(['wallets' => $wallets, 'statuses' => $statuses]);

        return $qb;
    }

    public function getUserCompletedTransactionsQueryBuilder(User $user)
    {
        $qb = clone $this->getUserCompletedTransactionsBaseQueryBuilder($user);
        $qb->select('d, m, fiat')
            ->join('m.withCurrency', 'fiat');

        return $qb;
    }

    public function getUserCompletedTransactionsBaseQueryBuilderWithTxOnly(User $user)
    {
        $wallets = $user->getWallets()->toArray();
        $statuses = [Order::STATUS_COMPLETED, Order::STATUS_CANCELLED];
        $qb = $this->createQueryBuilder('d')
            ->select('d')
            ->join('d.market', 'm')
            ->innerJoin('Btc\CoreBundle\Entity\Transaction', 'tx', Expr\Join::WITH, 'tx.order = d')
            ->where('d.inWallet IN (:wallets)')
            ->andWhere('d.status IN (:statuses)')
            ->orderBy('d.updatedAt', 'desc')
            ->orderBy('d.id', 'desc')
            ->distinct()
            ->setParameters([
                'wallets' => $wallets,
                'statuses' => $statuses,
            ]);

        return $qb;
    }

    public function getUserOpenOrderWithLimit(User $user, $limit = null)
    {
        $wallets = $user->getWallets()->toArray();

        $qb = $this->createQueryBuilder('d')
            ->select('d, m, virt, fiat')
            ->join('d.market', 'm')
            ->join('m.currency', 'virt')
            ->join('m.withCurrency', 'fiat')
            ->where('d.inWallet IN (:wallets)')
            ->andWhere('d.status = (:status)')
            ->orderBy('d.createdAt', 'DESC')
            ->setParameters([
                'wallets' => $wallets,
                'status' => Order::STATUS_OPEN,
            ])
            ->setMaxResults($limit);

        return $qb->getQuery()->getResult();
    }

    public function save(Order $order, $flush = false)
    {
        $this->getEntityManager()->persist($order);

        if ($flush === true) {
            $this->getEntityManager()->flush();
        }

        return $order;
    }

    public function flushAll()
    {
        $this->getEntityManager()->flush();
    }

    public function getOpenLimitOrders(array $wallets, Market $market, $sort, $offset, $limit)
    {
        $qb = $this->createQueryBuilder('d')
            ->select('d')
            ->where('d.inWallet IN (:wallets)')
            ->andWhere('d.status = (:status)')
            ->andWhere('d.type = (:type)')
            ->andWhere('d.market = (:market)')
            ->orderBy('d.updatedAt', $sort)
            ->setParameters([
                'wallets' => $wallets,
                'market' => $market,
                'status' => Order::STATUS_OPEN,
                'type' => Order::TYPE_LIMIT,
            ])
            ->setFirstResult($offset)
            ->setMaxResults($limit);

        return $qb->getQuery()->getResult();
    }

    public function getCompletedAndCanceledOrders(array $wallets, Market $market, $sort, $offset, $limit)
    {
        $qb = $this->createQueryBuilder('d')
            ->select('d')
            ->where('d.inWallet IN (:wallets)')
            ->andWhere('d.status IN (:status)')
            ->andWhere('d.market = (:market)')
            ->orderBy('d.updatedAt', $sort)
            ->setParameters([
                'wallets' => $wallets,
                'market' => $market,
                'status' => [
                    Order::STATUS_COMPLETED,
                    Order::STATUS_CANCELLED,
                ],
            ])
            ->setFirstResult($offset)
            ->setMaxResults($limit);

        return $qb->getQuery()->getResult();
    }

    public function getNotClosedOrder($id)
    {
        $qb = $this->createQueryBuilder('d')
            ->select('d')
            ->where('d.status NOT IN (:statuses)')
            ->andWhere('d.id = (:id)')
            ->setParameters([
                'id' => $id,
                'statuses' => [
                    Order::STATUS_PENDING_CANCEL,
                    Order::STATUS_CLOSED,
                ],
            ]);

        return $qb->getQuery()->getOneOrNullResult();
    }
}
