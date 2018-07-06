<?php

namespace Btc\FrontendApiBundle\Service;

use Btc\CoreBundle\Entity\Plan\Payment\LimitPlan;
use Btc\CoreBundle\Entity\Preference;
use Btc\CoreBundle\Entity\UserPreference;
use Btc\CoreBundle\Util\GeneratorInterface;
use Btc\CoreBundle\Entity\User;
use Btc\CoreBundle\Entity\Currency;
use Btc\CoreBundle\Entity\Wallet;
use Btc\CoreBundle\Entity\UserFeeSet;
use Btc\CoreBundle\Entity\FeeSet;
use Btc\CoreBundle\Entity\Plan\Payment\LimitAssignment;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface;

class UserRegistrationService
{
    private $generator;
    private $encoderFactory;
    private $em;

    public function __construct(
        EncoderFactoryInterface $encoderFactory,
        GeneratorInterface $generator,
        EntityManager $entityManager
    ) {
        $this->generator = $generator;
        $this->encoderFactory = $encoderFactory;
        $this->em = $entityManager;
    }

    public function initUser(User $user)
    {
        $username = $this->generator->generateUsername();
        $username = $this->checkIfUsernameExists($username);
        $user->setUsername($username);
        $user->setPlainPassword($this->generator->generatePassword());
        $user->addRole(User::FORCE_CHANGE_PASSWORD);
    }

    public function encryptPassword(User $user)
    {
        $encoder = $this->encoderFactory->getEncoder($user);
        $user->setPassword($encoder->encodePassword($user->getPlainPassword(), $user->getSalt()));
    }

    public function createWallets(EntityManager $em, User $user)
    {
        $currencies = $em->getRepository(Currency::class)->findAll();

        array_walk($currencies, function (Currency $currency) use ($user, $em) {
            $wallet = new Wallet;
            $wallet->setCurrency($currency);
            $user->addWallet($wallet);

            $em->persist($wallet);
        });
    }

    public function assignDefaultPlans(User $user)
    {
        // assign payment limit plan
        if ($user->hasRole('ROLE_VERIFIED_BUSINESS')) {
            $plan = 'verified-business';
        } elseif ($user->hasRole('ROLE_VERIFIED_PERSONAL')) {
            $plan = 'verified-personal';
        } else {
            $plan = 'unverified';
        }

        $plan = $this->em->getRepository(LimitPlan::class)->findOneBy(['slug' => $plan]);

        $assignment = new LimitAssignment();
        $assignment->setPlan($plan);
        $assignment->setUser($user);
        $this->em->persist($assignment);

        // assign trading fees
        $defaultFeeSet = $this->em->getRepository(FeeSet::class)
            ->findOneBy(['default' => 1]);

        if ($defaultFeeSet instanceof FeeSet) {
            $defaultFeeSet = $this->revaluateVolumeBasedSet($defaultFeeSet);

            $userFeeSet = new UserFeeSet;
            $userFeeSet->setUser($user);
            $userFeeSet->setFeeSet($defaultFeeSet);
            $userFeeSet->setFallbackFeeSet($defaultFeeSet);

            $this->em->persist($userFeeSet);
        }
    }

    public function assignDefaultPreferences(User $user)
    {
        $preference = $this->em->getRepository(Preference::class)
            ->findOneBy(['slug' => 'preference.email_ip_notification']);

        $userPreference = new UserPreference();
        $userPreference->setPreference($preference);
        $userPreference->setUser($user);
        $userPreference->setValue(1);

        $this->em->persist($userPreference);
    }

    public function setNewsletterPreference(User $user, $status)
    {
        $preference = $this->em->getRepository(Preference::class)
            ->findOneBy(['slug' => 'preference.newsletter']);

        $userPreference = new UserPreference();
        $userPreference->setPreference($preference);
        $userPreference->setValue($status);
        $user->addPreference($userPreference);

        $this->em->persist($userPreference);
    }

    private function revaluateVolumeBasedSet(FeeSet $feeSet)
    {
        if (!$feeSet->isStandard()) {
            return $feeSet;
        }

        foreach ($feeSet->getChildren() as $child) {
            if (floatval($child->getRule()) === 0.00) {
                return $child;
            }
        }
    }

    private function checkIfUsernameExists($username)
    {
        // prepare uniq username check query
        $query = $this->em->createQueryBuilder()
            ->select('COUNT(u.id)')
            ->from('BtcCoreBundle:User', 'u')
            ->where('u.username = :username')
            ->getQuery()
            ->setParameters(compact('username'));

        if (intval($query->getSingleScalarResult())) {
            return $this->checkIfUsernameExists($this->generator->generateUsername());
        }

        return $username;
    }
}
