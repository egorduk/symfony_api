<?php

namespace Btc\FrontendApiBundle\Repository;

use Btc\CoreBundle\Entity\User;
use Btc\CoreBundle\Entity\UserPersonalInfo;
use Btc\CoreBundle\Entity\Verification;
use Btc\CoreBundle\Entity\UserBusinessInfo;
use Doctrine\ORM\EntityManager;

class VerificationRepository
{
    private $em;

    public function __construct(EntityManager $entityManager)
    {
        $this->em = $entityManager;
    }

    public function createVerification(User $user)
    {
        $verification = new Verification();
        $verification->setUser($user);
        $verification->setPersonalInfo(new UserPersonalInfo());
        $verification->setBusinessInfo(new UserBusinessInfo());

        return $verification;
    }

    public function save(Verification $object, $flush = false)
    {
        $this->em->persist($object);

        if ($flush === true) {
            $this->em->flush();
        }

        return $object;
    }
}
