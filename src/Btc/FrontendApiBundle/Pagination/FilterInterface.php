<?php

namespace Btc\FrontendApiBundle\Pagination;

use Doctrine\ORM\QueryBuilder;
use Symfony\Component\HttpFoundation\ParameterBag;

interface FilterInterface
{
    /**
     * Get filter name, used as query parameter name
     *
     * @return string
     */
    function name();

    /**
     * Apply filtration on query builer $qb
     * based on request $params
     *
     * @param \Doctrine\ORM\QueryBuilder $qb
     * @param \Symfony\Component\HttpFoundation\ParameterBag $params
     */
    function apply(QueryBuilder $qb, ParameterBag $params);

    /**
     * Get filtration options available
     *
     * @return array
     */
    function options();

    /**
     * Apply filter even if it does not have a parameter in request
     * useful when some defaults needs to be applied
     *
     * @return boolean
     */
    function applyDefault();

    /**
     * Get active option based on request $params
     *
     * @param array $params
     * @return string
     */
    function active(array $params);
}
