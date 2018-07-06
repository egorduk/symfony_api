<?php

namespace Btc\FrontendApiBundle\Repository;

use Btc\CoreBundle\Entity\Transfer;
use Btc\CoreBundle\Entity\User;
use Btc\CoreBundle\Entity\Withdraw\Withdraw;
use Btc\CoreBundle\Entity\Withdraw\WithdrawLog;
use Doctrine\ORM\EntityRepository;

class BaseWithdrawRepository extends EntityRepository
{
    public function findAllByUser(User $user)
    {
        $qb = $this->findAllByUserQueryBuilder($user);

        return $qb->getQuery()->getResult();
    }

    public function findAllByUserQueryBuilder(User $user)
    {
        $qb = $this->findAllWithDetailsQueryBuilder();
        $qb->andWhere('w.user = :user')
            ->setParameter('user', $user->getId())
            ->orderBy('d.id', 'desc');

        return $qb;
    }

    public function findWithdrawAmountsByUser(User $user)
    {
        $qb = $this->createQueryBuilder('d');
        $qb->select('SUM(d.amount - d.feeAmount) as withdrawalAmount', 'c.code', 'b.name')
            ->join('d.wallet', 'w')
            ->join('w.currency', 'c')
            ->join('d.bank', 'b')
            ->where('w.user = :user')
            ->andWhere('d.status = :status')
            ->setParameters(['user' => $user, 'status' => Withdraw::STATUS_COMPLETED])
            ->groupBy('b.name')
            ->addGroupBy('c.code')
            ->orderBy('c.code', 'ASC');

        return $qb->getQuery()->getResult();
    }

    public function findAllInternationalWireWithdrawsQueryBuilder()
    {
        return $this->findAllWithDetailsQueryBuilder(true)
            ->andWhere('b.slug = :international')
            ->setParameter('international', 'international-wire-transfer');
    }

    public function findAllInternationalWireWithdrawsByUserQueryBuilder($user)
    {
        return $this->findAllInternationalWireWithdrawsQueryBuilder()
            ->andWhere('w.user = :user')
            ->setParameter('user', $user);
    }

    public function findAllWithDetailsQueryBuilder($wire = false)
    {
        $qb = $this->createQueryBuilder('d');
        $qb->select('d')
            ->addSelect('w')
            ->addSelect('c')
            ->addSelect('u')
            ->addSelect('b')
            ->innerJoin('d.wallet', 'w')
            ->innerJoin('w.currency', 'c')
            ->innerJoin('w.user', 'u')
            ->innerJoin('d.bank', 'b');

        if (!$wire) {
            $qb->where('b.slug <> \'international-wire-transfer\'');
        }

        $qb->orderBy('d.id', 'desc');

        return $qb;
    }

    public function findAllWithDetails()
    {
        return $this->findAllWithDetailsQueryBuilder()->getQuery()->getResult();
    }

    public function findOneWithDetails($id)
    {
        $qb = $this->findOneWithDetailsQueryBuilder($id);

        return current($qb->getQuery()->getResult());
    }

    public function lock($id)
    {
        $db = $this->_em->getConnection();
        $lockSql = 'SELECT status FROM withdraw WHERE id = :id FOR UPDATE';
        return $db->fetchColumn($lockSql, ['id' => $id]);
    }

    /**
     * Updates status of withdrawal
     */
    public function updateStatus($id, $status)
    {
        $db = $this->_em->getConnection();
        $updateStatusQuery = "UPDATE withdraw SET status = :status, updated_at = NOW() WHERE id = :id";
        $db->executeUpdate($updateStatusQuery, compact('id', 'status'));
    }

    public function updateForeignStatusAndReference($id, $status, $reference)
    {
        $db = $this->_em->getConnection();
        $updateQuery = "UPDATE withdraw SET foreign_status = :status, foreign_tx_reference = :reference, updated_at = NOW() WHERE id = :id";
        $db->executeUpdate($updateQuery, compact('id', 'status', 'reference'));
    }

    public function updateForeignStatus($id, $status)
    {
        $db = $this->_em->getConnection();
        $updateQuery = "UPDATE withdraw SET foreign_status = :status, updated_at = NOW() WHERE id = :id";
        $db->executeUpdate($updateQuery, compact('id', 'status'));
    }

    public function addApiErrorWithdrawLog($id, $message, $data = [])
    {
        $this->addWithdrawLog(
            $id,
            WithdrawLog::TYPE_API,
            WithdrawLog::STATUS_ERROR,
            $message,
            $data
        );
    }

    public function addApiSuccessWithdrawLog($id, $message, $apiResponse)
    {
        $this->addWithdrawLog(
            $id,
            WithdrawLog::TYPE_API,
            WithdrawLog::STATUS_SUCCESS,
            $message,
            $apiResponse
        );
    }

    private function addWithdrawLog($withdrawalId, $type, $status, $message, $data = [])
    {
        $db = $this->_em->getConnection();

        $db->insert(
            'withdraw_log',
            [
                'withdrawal_id' => $withdrawalId,
                'type' => $type,
                'status' => $status,
                'message' => $message,
                'data' => base64_encode(serialize($data)),
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]
        );
    }

    public function getWithdrawsQueryBuilder(User $user)
    {
        return $this->createQueryBuilder('t')
            ->join('t.wallet', 'w')
            ->where('t.wallet IN (:wallets)')
            ->setParameter('wallets', $user->getWallets())
            ->orderBy('t.createdAt', 'desc');
    }

    public function getPendingWithdrawsByUser(User $user)
    {
        $status = [Transfer::STATUS_NEW, Transfer::STATUS_APPROVED];
        return $this->createQueryBuilder('w')
            ->where('w.status IN (:status)')
            ->andWhere('w.wallet IN (:wallets)')
            ->setParameters(['status' => $status, 'wallets' => $user->getWallets()])
            ->orderBy('w.createdAt', 'desc')
            ->getQuery()
            ->getResult();
    }

    public function findAllManualWithdrawsWithDetailsQueryBuilder()
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

        $qb->andWhere('d INSTANCE OF Btc\CoreBundle\Entity\Withdraw\ManualWithdraw');

        $qb->orderBy('d.id', 'desc');

        return $qb;
    }

    public function findOneManualWithdrawWithDetails($id)
    {
        $qb = $this->findOneWithDetailsQueryBuilder($id);
        $qb->andWhere('d INSTANCE OF Btc\CoreBundle\Entity\Withdraw\ManualWithdraw');

        $result = current($qb->getQuery()->getResult());

        return empty($result) ? null : $result;
    }

    /**
     * @param $id
     * @return \Doctrine\ORM\QueryBuilder
     */
    private function findOneWithDetailsQueryBuilder($id)
    {
        return $this->findAllWithDetailsQueryBuilder()
            ->where('d.id = :id')
            ->setMaxResults(1)
            ->setParameter('id', $id);
    }
}
