<?php

namespace Btc\FrontendApiBundle\Repository;

use Doctrine\ORM\EntityRepository;

class EmailTemplateRepository extends EntityRepository
{
    public function findOneByName($name)
    {
        return $this->findOneBy(compact('name'));
    }
}
