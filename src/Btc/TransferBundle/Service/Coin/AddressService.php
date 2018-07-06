<?php namespace Btc\TransferBundle\Service\Coin;

use Btc\CoreBundle\Entity\Currency;
use Btc\CoreBundle\Entity\User;
use Btc\TransferBundle\Gateway\Coin\AddressRepositoryInterface;
use Btc\TransferBundle\Gateway\Coin\Exceptions\NewAddressLimitReachedException;
use Exmarkets\PaymentCoreBundle\Gateway\Coin\CoinApiInterface;

class AddressService
{
    /**
     * @var \Exmarkets\PaymentCoreBundle\Gateway\Coin\CoinApiInterface
     */
    private $api;

    /**
     * @var \Btc\TransferBundle\Gateway\Coin\AddressRepositoryInterface
     */
    private $repository;

    public function __construct(
        CoinApiInterface $api,
        AddressRepositoryInterface $repository
    ) {
        $this->api = $api;
        $this->repository = $repository;
    }

    /**
     * Returns address for user where to deposit coins
     *
     * if an address is not generated it will generate and assign
     * address to current user
     *
     * @param User $user
     * @param Currency $currency
     * @return string Address where to deposit
     */
    public function getAddress(User $user, Currency $currency)
    {
        $address = $this->repository->findCurrentAddress($user, $currency);

        if (!$address) {
            $address = $this->api->getAccountAddress($this->getAccountNameForUser($user));
            $this->repository->assignNewAddress($user, $currency, $address);
        }

        return $address;
    }

    /**
     * @param User $user
     * @param Currency $currency
     * @return string New address where to deposit
     */
    public function getNewAddress(User $user, Currency $currency)
    {
        $newAddress = $this->api->getNewAddress($this->getAccountNameForUser($user));
        $this->repository->assignNewAddress($user, $currency, $newAddress);

        return $newAddress;
    }

    public function requestNewAddress(User $user, Currency $currency)
    {
        if (!$this->canRequestNewAddress($user, $currency)) {
            throw new NewAddressLimitReachedException();
        }

        return $this->getNewAddress($user, $currency);
    }

    /**
     * Returns formatted user account name
     *
     * @param User $user
     * @return string
     */
    protected function getAccountNameForUser(User $user)
    {
        return $user->getUsername();
    }

    public function canRequestNewAddress(User $user, $currency)
    {
        return $this->repository->canRequestNewAddress($user, $currency, 48*60*60);
    }
}
