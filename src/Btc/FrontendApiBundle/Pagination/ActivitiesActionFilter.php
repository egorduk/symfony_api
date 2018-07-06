<?php

namespace Btc\FrontendApiBundle\Pagination;

use Btc\FrontendApiBundle\Events\AccountActivityEvents;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\HttpFoundation\ParameterBag;

class ActivitiesActionFilter implements FilterInterface
{
    public function name()
    {
        return 'action';
    }

    public function apply(QueryBuilder $qb, ParameterBag $params)
    {
        if (($action = $this->active($params->all())) && $action !== AccountActivityEvents::ALL) {
            $qb->andWhere('a.action = :action')->setParameter('action', $action);
        }
    }

    public function applyDefault()
    {
        return false;
    }

    public function options()
    {
        return [
            AccountActivityEvents::ALL,
            AccountActivityEvents::TWO_FACTOR_ENABLED,
            AccountActivityEvents::TWO_FACTOR_DISABLED,
            AccountActivityEvents::PROFILE_EDIT_COMPLETED,
            AccountActivityEvents::REGISTRATION_COMPLETED,
            AccountActivityEvents::LOGIN,
            AccountActivityEvents::CUSTOM_LOGIN,
            AccountActivityEvents::CHANGE_PASSWORD_COMPLETED,
            AccountActivityEvents::PREFERENCES_UPDATED,
            AccountActivityEvents::LIMIT_BUY_ORDER,
            AccountActivityEvents::LIMIT_SELL_ORDER,
            AccountActivityEvents::MARKET_BUY_ORDER,
            AccountActivityEvents::MARKET_SELL_ORDER,
            AccountActivityEvents::DEPOSIT_REQUEST,
            AccountActivityEvents::WITHDRAW_REQUEST,
            AccountActivityEvents::DEPOSIT,
            AccountActivityEvents::WITHDRAW,
        ];
    }

    public function active(array $params)
    {
        return array_key_exists($this->name(), $params) ? $this->find($params[$this->name()]) : AccountActivityEvents::ALL;
    }

    private function find($option)
    {
        $type = array_search($option, $this->options(), true);

        return $type !== false ? $this->options()[$type] : AccountActivityEvents::ALL;
    }
}
