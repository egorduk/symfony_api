<?php

namespace Btc\FrontendApiBundle\Repository;

use Btc\CoreBundle\Entity\UserPersonalInfo;
use Doctrine\ORM\EntityManagerInterface;

class UserPersonalInfoRepository
{
    private $em;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->em = $entityManager;
    }

    public function save(UserPersonalInfo $object, $flush = false)
    {
        $this->em->persist($object);

        if ($flush === true) {
            $this->em->flush();
        }

        return $object;
    }
}
