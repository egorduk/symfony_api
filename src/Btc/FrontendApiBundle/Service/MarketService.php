<?php

namespace Btc\FrontendApiBundle\Service;

use Doctrine\ORM\EntityManager;

class MarketService extends RestService
{
    public function __construct(EntityManager $em, $entityClass)
    {
        parent::__construct($em, $entityClass);
    }
}
