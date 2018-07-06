<?php

namespace Btc\ApiBundle\Presenter;

use Btc\ApiBundle\Model\Voucher;

class VoucherPresenter implements PresenterInterface
{
    private $voucher;

    public function __construct(Voucher $voucher)
    {
        $this->voucher = $voucher;
    }

    public function presentAsJson()
    {
        return [
            'amount' => bcadd($this->voucher->getAmount(), 0, 8),
            'currency' => $this->voucher->getCurrency()->getCode(),
            'code' => $this->voucher->getCode(),
            'status' => Voucher::$statusMap[$this->voucher->getStatus()],
            'created_at' => $this->voucher->getCreatedAt()->getTimestamp(),
            'redeemed_at' => $this->voucher->getRedeemedAt() ?
                $this->voucher->getRedeemedAt()->getTimestamp() : 0
        ];
    }
}
