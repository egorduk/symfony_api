<?php

namespace Btc\FrontendApiBundle\Repository;

use Btc\CoreBundle\Entity\CoinTransaction;
use Btc\CoreBundle\Entity\Deposit\Deposit;
use Btc\CoreBundle\Entity\Deposit\DepositLog;
use Btc\CoreBundle\Entity\User;
use Btc\CoreBundle\Entity\Wallet;
use Btc\TransferBundle\Gateway\Coin\TransactionRepositoryInterface;
use Doctrine\ORM\EntityRepository;

class CoinTransactionRepository extends EntityRepository implements TransactionRepositoryInterface
{
    private $currencyCode = 'btc';

    public function setCurrencyByCode($currencyCode)
    {
        $this->currencyCode = $currencyCode;
    }

    /**
     * @param string $tx transaction id from coins
     *
     * @return CoinTransaction|false if not found
     */
    public function findTransaction($tx)
    {
        $criteria = ['txId' => $tx];
        if ($this->currencyCode) {
            $criteria['currency'] = $this->getCurrencyByCode();
        }

        return $this->findOneBy($criteria) ?: false;
    }

    /**
     * Creates transactions.
     *
     * @param array $txDetails
     *
     * @return mixed
     */
    public function create($txDetails)
    {
        $entity = new CoinTransaction();
        $this->setDetailsToEntity($txDetails, $entity);

        // do some stuff with something
        $this->_em->persist($entity);
        $this->_em->flush($entity);

        return $entity;
    }

    /**
     * Updates model with transaction data.
     *
     * @param mixed $entity
     * @param array $txDetails
     */
    public function update($entity, $txDetails)
    {
        $this->setDetailsToEntity($txDetails, $entity);

        // do some stuff with something
        $this->_em->persist($entity);
        $this->_em->flush($entity);
    }

    /**
     * @param $txDetails
     * @param $tx
     */
    protected function setDetailsToEntity($txDetails, CoinTransaction $tx)
    {
        $this->setCurrencyIfAvailable($tx);

        $details = current($txDetails['details']);

        $tx->setAccount($details['account']);
        $tx->setAddress($details['address']);
        $tx->setCategory($details['category']);
        $tx->setAmount($txDetails['amount']);
        $tx->setConfirmations($txDetails['confirmations']);
        $tx->setBlockHash($txDetails['blockhash']);
        $tx->setBlockIndex($txDetails['blockindex']);
        $tx->setBlockTime($txDetails['blocktime']);
        $tx->setTxId($txDetails['txid']);
        $tx->setWalletConflicts($txDetails['walletconflicts']);
        $tx->setDetails($txDetails['details']);

        $tx->setTime($txDetails['time']);
        $tx->setTimeReceived($txDetails['timereceived']);
    }

    /**
     * @param CoinTransaction $tx
     */
    protected function setCurrencyIfAvailable(CoinTransaction $tx)
    {
        if ($this->currencyCode) {
            $tx->setCurrency($this->getCurrencyByCode());
        }
    }

    /**
     * @return mixed
     */
    protected function getCurrencyByCode()
    {
        $currency = $this->_em->getRepository('BtcCoreBundle:Currency')->findOneByCode($this->currencyCode);

        return $currency;
    }

    public function isProcessed($transaction)
    {
        return $transaction->getDepositLog() !== null;
    }

    public function createDepositReference(CoinTransaction $transaction)
    {
        // deposits only go with receive transactions
        if ($transaction->getCategory() != 'receive') {
            return;
        }

        $bankRepository = $this->_em->getRepository('BtcCoreBundle:Bank');
        $bank = $bankRepository->findOneByName([$transaction->getCurrency()->getCode()]);

        $deposit = new Deposit();
        $deposit->setAmount($transaction->getAmount());
        $deposit->setBank($bank);
        $deposit->completed();
        // wallet see where to put this
        $wallet = $this->getUserWalletByAccount($transaction->getAccount());
        $wallet->credit($transaction->getAmount());
        $deposit->setWallet($wallet); // find model

        $depositLog = new DepositLog();
        $depositLog->setTransactionReference($transaction);
        $depositLog->setType(DepositLog::TYPE_TX_IN);
        $depositLog->setStatus(DepositLog::STATUS_SUCCESS);
        $depositLog->setMessage('Incoming crypto currency tx');
        $depositLog->setData('Empty data');
        $depositLog->setOldStatus(DepositLog::STATUS_SUCCESS);
        $depositLog->setNewStatus(DepositLog::STATUS_SUCCESS);
        $depositLog->setReferencedTransfer($deposit);
        $deposit->addLog($depositLog);

        $this->_em->persist($deposit);
        $this->_em->persist($wallet);
        $this->_em->flush();

        return $deposit;
    }

    /**
     * @param string $account
     *
     * @return \Btc\CoreBundle\Entity\Wallet
     */
    protected function getUserWalletByAccount($account)
    {
        $id = explode('-', $account)[1];

        $userRepository = $this->_em->getRepository(User::class);
        $walletRepository = $this->_em->getRepository(Wallet::class);

        $user = $userRepository->find($id);
        $wallet = $walletRepository->findOneBy(['user' => $user, 'currency' => $this->getCurrencyByCode()]);

        return $wallet;
    }
}
