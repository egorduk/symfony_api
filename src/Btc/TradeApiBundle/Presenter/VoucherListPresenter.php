<?php

namespace Btc\ApiBundle\Presenter;

use Btc\ApiBundle\Model\Voucher;

class VoucherListPresenter implements PresenterInterface
{
    private $vouchers;

    public function __construct(array $vouchers = [])
    {
        $this->vouchers = $vouchers;
    }

    public function presentAsJson()
    {
        return ['vouchers' => array_map(function (Voucher $v) {
            return [
                'amount' => bcadd($v->getAmount(), 0, 8),
                'currency' => $v->getCurrency()->getCode(),
                'code' => $v->getCode(),
                'status' => Voucher::$statusMap[$v->getStatus()],
                'created_at' => $v->getCreatedAt()->getTimestamp(),
                'redeemed_at' => $v->getRedeemedAt() ?
                    $v->getRedeemedAt()->getTimestamp() : 0
            ];
        }, $this->vouchers)];
    }
}
