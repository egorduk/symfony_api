<?php

namespace spec\Btc\CoreBundle\Model;

use Btc\CoreBundle\Entity\Currency;
use Btc\CoreBundle\Entity\User;
use Btc\CoreBundle\Entity\Wallet;
use Btc\CoreBundle\Model\UserWallets;
use PhpSpec\ObjectBehavior;
use Doctrine\Common\Collections\ArrayCollection;

class UserWalletsSpec extends ObjectBehavior
{
    public function let(
        User $user,
        Wallet $btcWallet,
        Wallet $usdWallet,
        Wallet $ltcWallet,
        Wallet $eurWallet,
        Currency $fCurrency1,
        Currency $cCurrency1,
        Currency $fCurrency2,
        Currency $cCurrency2,
        ArrayCollection $wallets
    ) {
        $fCurrency1->getCode()->willReturn('USD');
        $fCurrency1->isCrypto()->willReturn(false);
        $cCurrency1->getCode()->willReturn('BTC');
        $cCurrency1->isCrypto()->willReturn(true);
        $btcWallet->getCurrency()->willReturn($cCurrency1);
        $usdWallet->getCurrency()->willReturn($fCurrency1);
        $fCurrency2->getCode()->willReturn('EUR');
        $fCurrency2->isCrypto()->willReturn(false);
        $cCurrency2->getCode()->willReturn('LTC');
        $cCurrency2->isCrypto()->willReturn(true);
        $ltcWallet->getCurrency()->willReturn($cCurrency2);
        $eurWallet->getCurrency()->willReturn($fCurrency2);
        $wallets->toArray()->willReturn(
            [
                $btcWallet,
                $ltcWallet,
                $usdWallet,
                $eurWallet,
            ]
        );
        $user->getWallets()->willReturn($wallets);

        $this->beConstructedWith($user);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(UserWallets::class);
    }

    public function it_should_return_wallet_by_currency_object(Currency $currency, $usdWallet)
    {
        $currency->getCode()->willReturn('USD');
        $this->oneByCurrency($currency)->shouldReturn($usdWallet);
    }

    public function it_should_return_wallet_by_currency_code($btcWallet)
    {
        $this->oneByCurrency('BTC')->shouldReturn($btcWallet);
    }

    public function it_should_return_all_crypto_wallets($btcWallet, $ltcWallet)
    {
        $this->allCrypto()->shouldReturn([$btcWallet, $ltcWallet]);
    }

    public function it_should_return_all_fiat_wallets($usdWallet, $eurWallet)
    {
        $this->allFiat()->shouldReturn([$usdWallet, $eurWallet]);
    }
}
