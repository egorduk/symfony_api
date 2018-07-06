<?php

namespace Btc\FrontendApiBundle\Repository;

use Btc\CoreBundle\Entity\Deposit\Deposit;
use Btc\CoreBundle\Entity\Deposit\DepositLog;
use Btc\CoreBundle\Entity\Transfer;
use Btc\CoreBundle\Entity\User;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\QueryBuilder;

class BaseDepositRepository extends EntityRepository
{
    public function findAllByUser(User $user)
    {
        $qb = $this->findAllWithDetailsQueryBuilder();
        $qb->where('w.user = :user');
        $qb->setParameter('user', $user->getId());

        return $qb->getQuery()->getResult();
    }

    public function findAllByUserQueryBuilder(User $user)
    {
        $qb = $this->findAllWithDetailsQueryBuilder();
        $qb->andWhere('w.user = :user');
        $qb->setParameter('user', $user->getId());
        $qb->orderBy('d.id', 'desc');
        return $qb;
    }

    public function findDepositAmountsByUser(User $user)
    {
        $qb = $this->createQueryBuilder('d');
        $qb->select('SUM(d.amount - d.feeAmount) as depositAmount', 'c.code', 'b.name')
            ->join('d.wallet', 'w')
            ->join('w.currency', 'c')
            ->join('d.bank', 'b')
            ->where('w.user = :user')
            ->andWhere('d.status = :status')
            ->setParameters(['user' => $user, 'status' => Deposit::STATUS_COMPLETED])
            ->groupBy('b.name')
            ->addGroupBy('c.code')
            ->orderBy('c.code', 'ASC')
            ->addOrderBy('b.name', 'ASC');

        return $qb->getQuery()->getResult();
    }

    public function findAllWithDetailsQueryBuilder($wire = false)
    {
        $qb = $this->createQueryBuilder('d');
        $qb->select('d')
            ->addSelect('w')
            ->addSelect('c')
            ->addSelect('u')
            ->addSelect('b');

        $qb->innerJoin('d.wallet', 'w')
            ->innerJoin('w.currency', 'c')
            ->innerJoin('w.user', 'u')
            ->innerJoin('d.bank', 'b');

        if (!$wire) {
            $qb->where('b.slug <> \'international-wire-transfer\'');
        }

        $qb->orderBy('d.id', 'DESC');
        return $qb;
    }

    public function findAllInternationalWireDepositsQueryBuilder()
    {
        return $this->findAllWithDetailsQueryBuilder(true)
            ->andWhere('b.slug = :international')
            ->setParameter('international', 'international-wire-transfer');
    }

    public function findAllInternationalWireDepositsByUserQueryBuilder($user)
    {
        $qb = $this->findAllInternationalWireDepositsQueryBuilder();
        $qb->andWhere('w.user = :user');
        $qb->setParameter('user', $user);
        return $qb;
    }

    public function findAllWithDetails()
    {
        return $this->findAllWithDetailsQueryBuilder()->getQuery()->getResult();
    }

    public function findOneWithDetails($id)
    {
        $qb = $this->findAllWithDetailsQueryBuilder()
            ->where('d.id = :deposit');
        $qb->setMaxResults(1);
        $qb->setParameter('deposit', $id);

        return current($qb->getQuery()->getResult());
    }

    public function findOneByUser(User $user, Deposit $deposit)
    {
        $qb = $this->createQueryBuilder('d');
        $qb->innerJoin('d.wallet', 'w')
            ->innerJoin('d.bank', 'b')
            ->where('w.user = :user')
            ->andWhere('d.id = :deposit');
        $qb->setParameters(
            [
                'user' => $user->getId(),
                'deposit' => $deposit->getId()
            ]
        );

        return current($qb->getQuery()->getResult());
    }

    public function lock($id)
    {
        $db = $this->_em->getConnection();
        $lockSql = 'select status from deposit where id = :id FOR UPDATE';
        return $db->fetchColumn($lockSql, ['id' => $id]);
    }

    /**
     * Updates status of withdrawal
     */
    public function updateStatus($id, $status)
    {
        $db = $this->_em->getConnection();

        $updateStatusQuery = "UPDATE deposit SET status = :status WHERE id = :id";
        $params = ['id' => $id, 'status' => $status];

        $db->executeUpdate($updateStatusQuery, $params);
    }

    public function updateForeignStatusAndReference($id, $status, $reference)
    {
        $db = $this->_em->getConnection();

        $updateQuery = "UPDATE deposit SET foreign_status = :status, foreign_tx_reference = :reference WHERE id = :id";
        $params = ['id' => $id, 'status' => $status, 'reference' => $reference];

        $db->executeUpdate($updateQuery, $params);
    }

    public function updateForeignStatus($id, $status)
    {
        $db = $this->_em->getConnection();

        $updateQuery = "UPDATE deposit SET foreign_status = :status WHERE id = :id";
        $params = ['id' => $id, 'status' => $status];

        $db->executeUpdate($updateQuery, $params);
    }

    public function addApiErrorDepositLog($id, $message, $data = [])
    {
        $this->addDepositLog(
            $id,
            DepositLog::TYPE_API,
            DepositLog::STATUS_ERROR,
            $message,
            $data
        );
    }

    public function addApiSuccessDepositLog($id, $message, $apiResponse)
    {
        $this->addDepositLog(
            $id,
            DepositLog::TYPE_API,
            DepositLog::STATUS_SUCCESS,
            $message,
            $apiResponse
        );
    }

    private function addDepositLog($depositId, $type, $status, $message, $data = [])
    {
        $db = $this->_em->getConnection();

        $db->insert(
            'deposit_log',
            [
                'deposit_id' => $depositId,
                'type' => $type,
                'status' => $status,
                'message' => $message,
                'data' => base64_encode(serialize($data)),
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]
        );
    }

    public function getDepositsQueryBuilder(User $user)
    {
        return $this->createQueryBuilder('t')
            ->join('t.wallet', 'w')
            ->where('t.wallet IN (:wallets)')
            ->setParameter('wallets', $user->getWallets())
            ->orderBy('t.createdAt', 'desc');
    }

    /**
     * Returns unconfirmed wire user deposit
     *
     * @throws NoResultException when deposit was not found
     * @param User $user
     * @param int $deposit
     * @return mixed
     */
    public function getUserUnconfirmedWireDeposit(User $user, $deposit)
    {
        $qb = $this->getDepositsQueryBuilder($user)
            ->andWhere('t.id = :id')
            ->andWhere('t.status = :status')
            ->andWhere('t INSTANCE OF Btc\CoreBundle\Entity\WireDeposit')
            ->setParameter('id', $deposit)
            ->setParameter('status', 'new')
            ->setMaxResults(1);

        return $qb->getQuery()->getSingleResult();
    }

    public function findUserInternationalDeposits(User $user)
    {
        $qb = $this->getDepositsQueryBuilder($user);
        $qb->join('t.bank', 'b');
        $qb->andWhere('b.slug = :slug')->setParameter('slug', 'international-wire-transfer');

        return $qb->getQuery()->getResult();
    }

    public function excludeNewStatus(QueryBuilder $qb)
    {
        $unwanted = Transfer::STATUS_NEW;
        $qb->andWhere('t.status != :unwanted')
            ->setParameter('unwanted', $unwanted);

        return $qb;
    }
}
