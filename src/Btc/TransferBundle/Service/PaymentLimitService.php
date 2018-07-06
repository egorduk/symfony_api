<?php

namespace Btc\TransferBundle\Service;

use Doctrine\DBAL\Connection;
use Doctrine\Common\Cache\Cache;
use Btc\CoreBundle\Entity\Superclass\Transfer;
use Btc\CoreBundle\Entity\User;
use Btc\CoreBundle\Entity\Currency;
use Btc\Component\Market\Model\PaymentLimit;
use Btc\Component\Market\Model\PaymentLimitCollection;

class PaymentLimitService
{
    private $db;

    public function __construct(Connection $db)
    {
        $this->db = $db;
    }

    public function withdrawals(User $user, $currencyType = Currency::ANY)
    {
        // depend on currency types needed
        $currencyAddon = '';
        if ($currencyType !== Currency::ANY) {
            $currencyAddon = ' AND c.crypto = '.($currencyType === Currency::VIRTUAL ? '1' : '0');
        }
        // fetch withdrawal limits based on paymnent plan assignment
        $limitsSql = <<<SQL
SELECT l.*, c.code, w.amount_available AS available
FROM plan_payment_limit_withdrawals AS l
INNER JOIN plan_payment_limits AS p ON p.id = l.plan_id
INNER JOIN plan_payment_limit_assignments AS a ON a.plan_id = l.plan_id
INNER JOIN currency AS c ON l.currency_id = c.id
INNER JOIN wallet AS w ON w.user_id = a.user_id AND w.currency_id = c.id
WHERE a.user_id = ?{$currencyAddon}
SQL;
        $limits = $this->db->fetchAll($limitsSql, [$user->getId()]);
        // load all valid withdrawals within last month, week and day
        $month = new \DateTime('-1 month');
        $week = new \DateTime('-1 week');
        $day = new \DateTime('-1 day');
        // @TODO: it uses a temporary table for filesort, need to update sql
        $withdrawnSql = <<<SQL
SELECT
  w.currency_id,
  SUM(IF(wd.updated_at > ?, wd.amount, 0)) AS daily,
  SUM(IF(wd.updated_at > ?, wd.amount, 0)) AS weekly,
  SUM(IF(wd.updated_at > ?, wd.amount, 0)) AS monthly
FROM withdrawal AS wd
INNER JOIN wallet AS w ON w.id = wd.wallet_id
INNER JOIN currency AS c ON w.currency_id = c.id
WHERE w.user_id = ?
  AND wd.status NOT IN (?)
  AND wd.updated_at >= ?{$currencyAddon}
GROUP BY wd.wallet_id
SQL;
        $statuses = [Transfer::STATUS_INTERNAL, Transfer::STATUS_CANCELED, Transfer::STATUS_FAILED];
        $params = [$day, $week, $month, $user->getId(), $statuses, $month];
        $types = ['datetime', 'datetime', 'datetime', 'integer', Connection::PARAM_STR_ARRAY, 'datetime'];
        $withdrawn = [];
        foreach ($this->db->fetchAll($withdrawnSql, $params, $types) as $w) {
            $withdrawn[$w['currency_id']] = $w;
        }

        // update limits with withdrawn and remaining amounts
        foreach ($limits as &$limit) {
            $c = $limit['currency_id'];
            $max = $limit['daily'];
            foreach (['daily', 'weekly', 'monthly'] as $key) {
                $out = array_key_exists($c, $withdrawn) ? $withdrawn[$c][$key] : 0;
                $limit['in_'.$key] = $in = bcsub($limit[$key], $out, 8);
                if (bccomp($max, $in, 8) === 1) {
                    $max = $in;
                }
            }
            $limit['allowed'] = $max;
        }
        return $this->transform($limits);
    }

    private function transform(array $rows)
    {
        $limits = [];
        foreach ($rows as $row) {
            $limits[] = new PaymentLimit(
                $row['currency_id'],
                $row['code'],
                $row['allowed'],
                $row['available'],
                $row['daily'],
                $row['weekly'],
                $row['monthly'],
                $row['in_daily'],
                $row['in_weekly'],
                $row['in_monthly'],
                $row['plan_id']
            );
        }
        return new PaymentLimitCollection($limits);
    }

    public function deposits(User $user, $currencyType = Currency::ANY)
    {
        // depend on currency types needed
        $currencyAddon = '';
        if ($currencyType !== Currency::ANY) {
            $currencyAddon = ' AND c.crypto = '.($currencyType === Currency::VIRTUAL ? '1' : '0');
        }
        // fetch withdrawal limits based on paymnent plan assignment
        $limitsSql = <<<SQL
SELECT l.*, c.code, w.amount_available AS available
FROM plan_payment_limit_deposits AS l
INNER JOIN plan_payment_limits AS p ON p.id = l.plan_id
INNER JOIN plan_payment_limit_assignments AS a ON a.plan_id = l.plan_id
INNER JOIN currency AS c ON l.currency_id = c.id
INNER JOIN wallet AS w ON w.user_id = a.user_id AND w.currency_id = c.id
WHERE a.user_id = ?{$currencyAddon}
SQL;
        $limits = $this->db->fetchAll($limitsSql, [$user->getId()]);
        // load all valid withdrawals within last month, week and day
        $month = new \DateTime('-1 month');
        $week = new \DateTime('-1 week');
        $day = new \DateTime('-1 day');
        // @TODO: it uses a temporary table for filesort, need to update sql
        $depositedSql = <<<SQL
SELECT
  w.currency_id,
  SUM(IF(wd.updated_at > ?, wd.amount, 0)) AS daily,
  SUM(IF(wd.updated_at > ?, wd.amount, 0)) AS weekly,
  SUM(IF(wd.updated_at > ?, wd.amount, 0)) AS monthly
FROM deposit AS wd
INNER JOIN wallet AS w ON w.id = wd.wallet_id
INNER JOIN currency AS c ON w.currency_id = c.id
WHERE w.user_id = ?
  AND wd.status NOT IN (?)
  AND wd.updated_at >= ?{$currencyAddon}
GROUP BY wd.wallet_id
SQL;
        $statuses = [Transfer::STATUS_INTERNAL, Transfer::STATUS_NEW, Transfer::STATUS_CANCELED, Transfer::STATUS_FAILED];
        $params = [$day, $week, $month, $user->getId(), $statuses, $month];
        $types = ['datetime', 'datetime', 'datetime', 'integer', Connection::PARAM_STR_ARRAY, 'datetime'];
        $deposited = [];
        foreach ($this->db->fetchAll($depositedSql, $params, $types) as $d) {
            $deposited[$d['currency_id']] = $d;
        }

        // update limits with withdrawn and remaining amounts
        foreach ($limits as &$limit) {
            $c = $limit['currency_id'];
            $max = $limit['daily'];
            foreach (['daily', 'weekly', 'monthly'] as $key) {
                $out = array_key_exists($c, $deposited) ? $deposited[$c][$key] : 0;
                $limit['in_'.$key] = $in = bcsub($limit[$key], $out, 8);
                if (bccomp($max, $in, 8) === 1) {
                    $max = $in;
                }
            }
            $limit['allowed'] = $max;
        }
        return $this->transform($limits);
    }
}

