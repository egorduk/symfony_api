<?php namespace Btc\TransferBundle\Gateway\Coin;

use Btc\CoreBundle\Entity\Currency;
use Btc\CoreBundle\Entity\User;

interface AddressRepositoryInterface
{
    /**
     * Returns current deposit address for bitcoin for user.
     *
     * User can regenerate his deposit address on demand,
     * so user can have many address'es in the past.
     *
     * @param User $user
     * @param Currency $currency
     * @return string|false Address or false when address was not found
     */
    public function findCurrentAddress(User $user, Currency $currency);

    /**
     * Assigns a new address to a user as current address for deposits.
     *
     * @param User $user
     * @param Currency $currency
     * @param string $address
     * @return void
     */
    public function assignNewAddress(User $user, Currency $currency, $address);

    /**
     * Returns whether user can request new address.
     *
     * @param User $user
     * @param Currency $currency
     * @param int $seconds throttle between requests.
     * @return bool
     */
    public function canRequestNewAddress(User $user, Currency $currency, $seconds);
}
