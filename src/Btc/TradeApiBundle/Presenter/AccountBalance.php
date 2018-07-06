<?php

namespace Btc\TradeApiBundle\Presenter;

use Btc\CoreBundle\Entity\Wallet;

class AccountBalance implements PresenterInterface
{
    private $wallets;


    public function __construct(array $wallets)
    {
        $this->wallets = $wallets;
    }

    /**
     * Present transactions
     *
     * @return array
     */
    public function presentAsJson()
    {
        $wallets = array_map(function (Wallet $w) {
            return [
                'currency' => $w->getCurrency()->getCode(),
                'total' => bcadd($w->getAmountTotal(), 0, 8),
                'reserved' => bcadd($w->getAmountReserved(), 0, 8),
                'available' => bcadd($w->getAmountAvailable(), 0, 8)
            ];
        }, $this->wallets);

        return compact('wallets');
    }
}
