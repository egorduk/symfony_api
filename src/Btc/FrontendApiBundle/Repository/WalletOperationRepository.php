<?php

namespace Btc\FrontendApiBundle\Repository;

use Btc\CoreBundle\Entity\Deposit;
use Btc\CoreBundle\Entity\Withdrawal;
use Btc\CoreBundle\Entity\WalletOperation;
use Doctrine\ORM\EntityRepository;

class WalletOperationRepository extends EntityRepository
{
    public function insertDepositOperation(Deposit $deposit)
    {
        $walletOperation = new WalletOperation();
        $walletOperation->setWallet($deposit->getWallet());
        $walletOperation->setCredit($deposit->getAmountAfterFee());
        $walletOperation->setType(WalletOperation::TYPE_DEPOSIT);
        $walletOperation->setDeposit($deposit);

        $this->_em->persist($walletOperation);
        $this->_em->flush();
    }

    public function insertDepositFeeOperation(Deposit $deposit)
    {
        $walletOperation = new WalletOperation();
        $walletOperation->setWallet($deposit->getWallet());
        $walletOperation->setExpense($deposit->getFeeAmount());
        $walletOperation->setType(WalletOperation::TYPE_DEPOSIT_FEE);
        $walletOperation->setDeposit($deposit);

        $this->_em->persist($walletOperation);
        $this->_em->flush();
    }

    public function insertWithdrawOperation(Withdrawal $withdrawal)
    {
        $walletOperation = new WalletOperation();
        $walletOperation->setWallet($withdrawal->getWallet());
        $walletOperation->setDebit($withdrawal->getAmountAfterFee());
        $walletOperation->setType(WalletOperation::TYPE_WITHDRAWAL);
        $walletOperation->setWithdrawal($withdrawal);

        $this->_em->persist($walletOperation);
        $this->_em->flush();
    }

    public function insertWithdrawFeeOperation(Withdrawal $withdrawal)
    {
        $walletOperation = new WalletOperation();
        $walletOperation->setWallet($withdrawal->getWallet());
        $walletOperation->setExpense($withdrawal->getFeeAmount());
        $walletOperation->setType(WalletOperation::TYPE_WITHDRAWAL_FEE);
        $walletOperation->setWithdrawal($withdrawal);

        $this->_em->persist($walletOperation);
        $this->_em->flush();
    }
}
