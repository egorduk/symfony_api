<?php

namespace Btc\TradeApiBundle\Presenter;

use Btc\Component\Market\Model\FeeSet;
use Btc\TradeApiBundle\Model\Volume;

class VolumeFeePresenter implements PresenterInterface
{
    private $volume;
    private $feeSet;

    public function __construct(Volume $volume, FeeSet $feeSet)
    {
        $this->volume = $volume;
        $this->feeSet = $feeSet;
    }

    public function presentAsJson()
    {
        $feePercents = [];

        foreach ($this->feeSet->getMarketFeePercents() as $marketFeePercent) {
            $feePercents[strtolower($marketFeePercent['market_id'])]['buy_percent'] = bcadd($marketFeePercent['buy_percent'], 0, 2);
            $feePercents[strtolower($marketFeePercent['market_id'])]['sell_percent'] = bcadd($marketFeePercent['sell_percent'], 0, 2);
        }

        return [
            'volume_currency' => 'USD',
            'volume' => bcadd($this->volume->getAmount(), 0, 8),
            'fee_plan' => $this->feeSet->getFeeName(),
            'fee_percent' => $feePercents,
        ];
    }
}
