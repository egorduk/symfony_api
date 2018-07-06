<?php

namespace Btc\FrontendApiBundle\Pagination;

class ActivitiesWithinDaysFilter extends TransactionsWithinDaysFilter
{
    protected function filterField()
    {
        return 'a.createdAt';
    }

    public function options()
    {
        return range(1, 31);
    }
}
