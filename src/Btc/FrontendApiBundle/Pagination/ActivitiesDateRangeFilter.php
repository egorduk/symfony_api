<?php

namespace Btc\FrontendApiBundle\Pagination;

use Symfony\Component\HttpFoundation\ParameterBag;
use Doctrine\ORM\QueryBuilder;

class ActivitiesDateRangeFilter implements FilterInterface
{
    public function name()
    {
        return 'dates';
    }

    public function apply(QueryBuilder $qb, ParameterBag $params)
    {
        $data = $params->all();

        if (!empty($data['dateFrom'])) {
            $qb->andWhere('a.createdAt >= :dateFrom')->setParameter('dateFrom', date('Y-m-d 00:00:00', strtotime($data['dateFrom'])));
        }

        if (!empty($data['dateTo']))  {
            $qb->andWhere('a.createdAt <= :dateTo')->setParameter('dateTo', date('Y-m-d 23:59:59', strtotime($data['dateTo'])));
        }
    }

    protected function filterField()
    {
        return 'updatedAt';
    }

    public function applyDefault()
    {
        return true;
    }

    public function options()
    {
        return [];
    }

    public function active(array $params)
    {
        return true;
    }
}
