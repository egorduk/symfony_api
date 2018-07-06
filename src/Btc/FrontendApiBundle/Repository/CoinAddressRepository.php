<?php

namespace Btc\FrontendApiBundle\Repository;

use Btc\CoreBundle\Entity\Currency;
use Btc\CoreBundle\Entity\DepositAddress;
use Btc\CoreBundle\Entity\User;
use Btc\TransferBundle\Gateway\Coin\AddressRepositoryInterface;
use Doctrine\ORM\EntityRepository;

class CoinAddressRepository extends EntityRepository implements AddressRepositoryInterface
{
    /**
     * Returns current deposit address for bitcoin for user.
     *
     * User can regenerate his deposit address on demand,
     * so user can have many address'es in the past.
     *
     * @param User     $user
     * @param Currency $currency
     *
     * @return string|false Address or false when address was not found
     */
    public function findCurrentAddress(User $user, Currency $currency)
    {
        $address = $this->findUserAddress($user, $currency);

        if (!$address) {
            return false;
        }

        return $address->getAddress();
    }

    /**
     * Assigns a new address to a user as current address for deposits.
     *
     * @param User     $user
     * @param Currency $currency
     * @param string   $address
     */
    public function assignNewAddress(User $user, Currency $currency, $address)
    {
        $depositAddress = new DepositAddress();
        $depositAddress->setUser($user);
        $depositAddress->setAddress($address);
        $depositAddress->setCurrency($currency);

        $this->_em->persist($depositAddress);
        $this->_em->flush($depositAddress);

        return;
    }

    /**
     * Returns whether user can request new address.
     *
     * @param User     $user
     * @param Currency $currency
     * @param int      $seconds  throttle between requests
     *
     * @return bool
     */
    public function canRequestNewAddress(User $user, Currency $currency, $seconds)
    {
        $address = $this->findUserAddress($user, $currency);
        /** @var DepositAddress $address */
        if (!$address) {
            return true;
        }
        $diff = time() - $address->getCreatedAt()->getTimestamp();

        return ($diff >= $seconds) ? true : false;
    }

    /**
     * @param User                            $user
     * @param \Btc\CoreBundle\Entity\Currency $currency
     *
     * @return DepositAddress|false
     */
    private function findUserAddress(User $user, Currency $currency)
    {
        $qb = $this->createQueryBuilder('a')
            ->join('a.currency', 'c')
            ->where('a.user = :user')
            ->andWhere('c.code = :code')
            ->orderBy('a.createdAt', 'DESC');

        $qb->setMaxResults(1)
            ->setParameter('user', $user)
            ->setParameter('code', $currency->getCode());

        return current($qb->getQuery()->getResult());
    }

    /**
     * Returns ordered list of user addresses.
     *
     * @param User     $user
     * @param Currency $currency
     *
     * @return array
     */
    public function findUserAddresses(User $user, Currency $currency)
    {
        return $this->findBy(['user' => $user, 'currency' => $currency], ['createdAt' => 'DESC']);
    }
}
