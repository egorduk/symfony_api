<?php

namespace Btc\TradeBundle\Controller;

use Btc\CoreBundle\Entity\Currency;
use Btc\CoreBundle\Entity\User;

trait TradeControllerExtension
{
    private $serviceWallets;
    private $user;

    /**
     * 500 code is because wallet must be available
     * if it is not, it is an application issue
     *
     * @param \Btc\CoreBundle\Entity\Currency $currency
     * @throws \RuntimeException if wallet was not found
     * @return \Btc\CoreBundle\Entity\Wallet
     */
    private function findWalletOr500(Currency $currency)
    {
        //todo: debug
        $wallets = !empty($this->serviceWallets) ? $this->serviceWallets : $this->get('wallets');
        $user =  !empty($this->user) ? $this->user : $this->getUser();
        $wallet = $wallets->findOneForUserAndCurrency($user, $currency);
        //todo eof debug

        //$wallet = $this->getServiceWallets()->findOneForUserAndCurrency($user = $this->getServiceUser(), $currency);


        //todo: - translator is not injected while calling from REST API
        if (!$wallet) {
            throw new \RuntimeException($this->get('translator')->trans(
                'order.error.wallet',
                ['%currency%' => $currency->getCode(), '%user%' => $user->getUsername()],
                'Trade'
            ));
        }

        return $wallet;
    }

    public function setServiceWallets($serviceWallets = null) {
        if ($serviceWallets != null) {
            $this->serviceWallets = $serviceWallets;
        } else {
            $this->serviceWallets = $this->get('wallets');
        }
    }

    public function getServiceWallets() {
        return $this->serviceWallets;
    }

    public function setServiceUser(User $user = null) {
        if ($user != null) {
            $this->user = $user;
        } else {
            $this->user = $this->getUser();
        }
    }

    public function getServiceUser() {
        return $this->user;
    }
}