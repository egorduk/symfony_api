<?php

namespace Btc\FrontendApiBundle\Service;

class QrCodeService
{
    /**
     * @var string
     */
    private $providerName;

    /**
     * Generates and returns and qr_code url with gcharts api.
     *
     * Size defaults to 200x200.
     *
     * @param string $totpProviderName
     * @param string $totpSecret
     * @param string $size
     *
     * @return string Url to generated QR CODE on gcharts
     */
    public function getUrl($totpProviderName, $totpSecret, $size = '200x200')
    {
        return $this->getGoogleChartsUrl($this->getTotpUrl($totpProviderName, $totpSecret), $size);
    }

    /**
     * Returns totp auth string.
     *
     * @param string $account
     * @param string $secret
     *
     * @see https://code.google.com/p/google-authenticator/wiki/KeyUriFormat
     *
     * @return string
     */
    private function getTotpUrl($account, $secret)
    {
        if ($this->providerName != null) {
            $account = $this->providerName.':'.$account;
        }

        return "otpauth://totp/{$account}?secret={$secret}";
    }

    /**
     * @param string $totpLink
     * @param string $size     in format 'widthxheight'
     *
     * @return string
     */
    private function getGoogleChartsUrl($totpLink, $size)
    {
        return "https://chart.googleapis.com/chart?chs={$size}&chld=M|0&cht=qr&chl={$totpLink}";
    }

    public function setProviderName($name)
    {
        $this->providerName = $name;
    }

    public function getProviderName()
    {
        return $this->providerName;
    }
}
