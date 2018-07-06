<?php

namespace Btc\FrontendApiBundle\Pagination;

use Doctrine\ORM\QueryBuilder;
use Symfony\Component\HttpFoundation\ParameterBag;

class TransactionsWithinDaysFilter implements FilterInterface
{
    public function name()
    {
        return 'days';
    }

    protected function filterField()
    {
        return 'd.updatedAt';
    }

    public function apply(QueryBuilder $qb, ParameterBag $params)
    {
        if ($days = intval($this->active($params->all()))) {
            $qb->andWhere($this->filterField().' >= :date')
                ->setParameter('date', new \DateTime('-'.$days.' days'));
        }
    }

    public function applyDefault()
    {
        return false;
    }

    public function options()
    {
        return [0, 1, 7, 30];
    }

    public function active(array $params)
    {
        return array_key_exists($this->name(), $params) ? $this->find($params[$this->name()]) : null;
    }

    private function find($option)
    {
        $type = array_search(intval($option), $this->options(), true);

        return $type !== false ? $this->options()[$type] : null;
    }
}
