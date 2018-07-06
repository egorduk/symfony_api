<?php

namespace Btc\FrontendApiBundle\Repository;

use Btc\CoreBundle\Entity\User;
use Doctrine\ORM\EntityRepository;
use Btc\CoreBundle\Entity\PhoneVerification;

class PhoneRepository extends EntityRepository implements PhoneVerificationRepositoryInterface
{
    public function findNotConfirmedByUser(User $user)
    {
        return $this->findOneBy([
            'user' => $user,
            'confirmed' => false,
        ]);
    }

    public function findConfirmed(User $user)
    {
        return $this->findOneBy([
            'user' => $user,
            'confirmed' => true,
        ]);
    }

    public function findRequestedByUserAndPin(User $user, $pin)
    {
        return $this->findOneBy(compact('user', 'pin'));
    }

    public function isVerified(User $user)
    {
        return $this->findConfirmed($user) ? true : false;
    }

    public function deleteAllUserVerifications(User $user)
    {
        foreach ($this->findBy(compact('user')) as $verification) {
            $this->_em->remove($verification);
        }
    }

    public function deleteVerification(PhoneVerification $verification)
    {
        $this->_em->remove($verification);
    }

    public function save(PhoneVerification $object, $flush = false)
    {
        $this->_em->persist($object);

        if ($flush === true) {
            $this->_em->flush();
        }

        return $object;
    }
}
