<?php

namespace Btc\FrontendApiBundle\Service;

use Doctrine\ORM\EntityManager;

class UserFeeSetService extends RestService
{
    public function __construct(EntityManager $em, $entityClass)
    {
        parent::__construct($em, $entityClass);
    }
}
