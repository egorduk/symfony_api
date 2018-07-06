<?php

namespace Btc\TradeBundle\Twig\Extension;

use Twig_Extension;
use Btc\CoreBundle\Entity\Currency;

/**
 * Twig extension for currency formatting and transformation
 */
class CurrencyExtension extends Twig_Extension
{
    private $fp;
    private $cp;
    private $filterName;

    public function __construct($filterName = 'price', $fiatPrecision = 2, $cryptoPrecision = 8)
    {
        $this->fp = $fiatPrecision;
        $this->cp = $cryptoPrecision;
        $this->fn = $filterName;
    }

    public function getFilters()
    {
        return [
            new \Twig_SimpleFilter($this->fn, [$this, 'priceFilter']),
            new \Twig_SimpleFilter('sign', [$this, 'currencySign']),
        ];
    }

    public function priceFilter($number, $currency, $useSign = true)
    {
        $code = $currency instanceof Currency ? $currency->getCode() : $currency;
        if (!is_numeric($number)) {
            return 0;
        }
        // @todo refactor this as this will get uglier and uglier with time
        switch ($code) {
            case 'EUR':
            case 'USD':
                $number = bcadd($number, 0, $this->fp); // floor is evil, use bcadd to cut down up to decimals
                return $useSign
                    ? ltrim($this->currencySign($code) . number_format($number, $this->fp, '.', ','))
                    : number_format($number, $this->fp, '.', ',');
            case 'BTC':
            case 'LTC':
            default:
                return $useSign
                    ? number_format($number, $this->cp, '.', ',') . ' ' . ltrim($this->currencySign($code))
                    : number_format($number, $this->cp, '.', ',');
        }
    }

    public function currencySign($currency)
    {
        $code = $currency instanceof Currency ? $currency->getCode() : $currency;
        switch ($code) {
            case 'EUR':
                return '€';
            case 'BTC':
                return 'BTC';
            case 'USD':
                return '$';
            case 'LTC':
                return 'LTC';
            default:
                return $code;
        }
    }

    public function getName()
    {
        return 'btc_trade_currency_' . $this->fn;
    }
}
