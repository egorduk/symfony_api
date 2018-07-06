<?php

namespace Btc\FrontendApiBundle\Repository;

use Doctrine\ORM\EntityRepository;

class SettingsRepository extends EntityRepository
{
    public function isNotificationEnabled($action)
    {
        return (bool) $this->createQueryBuilder('s')
            ->select('s.value')
            ->where('s.slug = :settingSlug')
            ->setCacheable(false)
            ->setParameter('settingSlug', $action.'-notification')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function findOneBySlug($slug = '')
    {
        return $this->findOneBy(['slug' => $slug]);
    }
}
