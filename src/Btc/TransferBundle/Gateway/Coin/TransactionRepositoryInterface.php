<?php namespace Btc\TransferBundle\Gateway\Coin;

use Btc\CoreBundle\Entity\CoinTransaction;

interface TransactionRepositoryInterface
{
    /**
     *
     */
    public function findTransaction($tx);

    /**
     * Creates transactions
     *
     * @param $something
     * @return mixed
     */
    public function create($something);

    /**
     * Updates model with transaction data
     *
     * @param object $entity
     * @param array $data
     * @return
     */
    public function update($entity, $data);

    public function isProcessed($transaction);

    public function createDepositReference(CoinTransaction $transaction);
} 
