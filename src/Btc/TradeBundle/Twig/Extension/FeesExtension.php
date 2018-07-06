<?php

namespace Btc\TradeBundle\Twig\Extension;

use Twig_Extension;

/**
 * Twig extension for fees formatting
 */
class FeesExtension extends Twig_Extension
{
    public function __construct(CurrencyExtension $currencyExtension)
    {
        $this->currencyExtension = $currencyExtension;
    }

    public function getFilters()
    {
        return [
            new \Twig_SimpleFilter('format_fees', [$this, 'formatFees'])
        ];
    }

    /**
     * @param $fees
     * @return array
     */
    public function formatFees($fees)
    {
        $formattedFees = [];
        foreach ($fees as $fee) {
            $isBankNew = true;
            foreach ($formattedFees as $key => $value) {
                if ($value['bank'] == $fee->getBank()->getName()) {
                    if ((double)$fee->getFixed()) {
                        $formattedFees[$key]['fee'] .= $value['hasFixed'] ? '/' : ' + ';
                        $formattedFees[$key]['fee'] .= $this->currencyExtension->priceFilter($fee->getFixed(), $fee->getCurrency());
                        $formattedFees[$key]['hasFixed'] = true;
                    }
                    $isBankNew = false;
                    break;
                }
            }
            if ($isBankNew) {
                $formattedFee['bank'] = $fee->getBank()->getName();
                $formattedFee['fee'] = number_format($fee->getPercent(), 1, '.', '') . '%';

                if ((double)$fee->getFixed()) {
                    $formattedFee['fee'] .= ' + ' . $this->currencyExtension->priceFilter(
                        $fee->getFixed(),
                        $fee->getCurrency()
                    );
                    $formattedFee['hasFixed'] = true;
                } else {
                    $formattedFee['hasFixed'] = false;
                }
                $formattedFee['term'] = $fee->getTerm();
                $formattedFees[] = $formattedFee;
            }
        }
        return $formattedFees;
    }

    public function getName()
    {
        return 'btc_trade_fees';
    }
}
