<?php

namespace Btc\FrontendApiBundle\Repository;

use Btc\CoreBundle\Entity\UserBusinessInfo;
use Doctrine\ORM\EntityManagerInterface;

class UserBusinessInfoRepository
{
    private $em;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->em = $entityManager;
    }

    public function save(UserBusinessInfo $object, $flush = false)
    {
        $this->em->persist($object);

        if ($flush === true) {
            $this->em->flush();
        }

        return $object;
    }
}
