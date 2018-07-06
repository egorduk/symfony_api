<?php

namespace Btc\FrontendApiBundle\Repository;

use Btc\FrontendApiBundle\Entity\CoinSubmit;
use Doctrine\ORM\EntityManager;

class CoinSubmissionRepository
{
    private $em;

    public function __construct(EntityManager $entityManager)
    {
        $this->em = $entityManager;
    }


    public function save(CoinSubmit $object)
    {
        $this->em->persist($object);
        $this->em->flush();
        return $object;
    }
}
