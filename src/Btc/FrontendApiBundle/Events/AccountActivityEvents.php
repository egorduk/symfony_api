<?php

namespace Btc\FrontendApiBundle\Events;

use Btc\CoreBundle\Entity\Order;

final class AccountActivityEvents
{
    const ALL = 'btc_user.security.all';

    const TWO_FACTOR_ENABLED = 'btc_user.security.two_factor_enabled';
    const TWO_FACTOR_DISABLED = 'btc_user.security.two_factor_disabled';

    const PROFILE_EDIT_COMPLETED = 'btc_user.security.profile_edit_completed';
    const REGISTRATION_COMPLETED = 'btc_user.security.registration_completed';
    const LOGIN = 'btc_user.security.login';
    const CUSTOM_LOGIN = 'btc_user.security.login_custom';
    const CHANGE_PASSWORD_COMPLETED = 'btc_user.security.change_password_completed';
    const PREFERENCES_UPDATED = 'btc_user.security.preferences_updated';

    const LIMIT_BUY_ORDER = 'btc_user.security.limit_buy';
    const LIMIT_SELL_ORDER = 'btc_user.security.limit_sell';
    const STOP_LIMIT_BUY_ORDER = 'btc_user.security.stop_limit_buy';
    const STOP_LIMIT_SELL_ORDER = 'btc_user.security.stop_limit_sell';
    const MARKET_BUY_ORDER = 'btc_user.security.market_buy';
    const MARKET_SELL_ORDER = 'btc_user.security.market_sell';
    const STOP_MARKET_BUY_ORDER = 'btc_user.security.stop_market_buy';
    const STOP_MARKET_SELL_ORDER = 'btc_user.security.stop_market_sell';

    const DEPOSIT = 'btc_user.security.deposit';
    const WITHDRAW = 'btc_user.security.withdraw';
    const DEPOSIT_REQUEST = 'btc_user.security.deposit_request';
    const WITHDRAW_REQUEST = 'btc_user.security.withdraw_request';

    const VOUCHER_REDEEM = 'btc_user.security.voucher_redeem';
    const VOUCHER_ISSUE = 'btc_user.security.voucher_issue';

    /**
     * @param string $orderSide
     * @param int    $orderType
     *
     * @return string
     */
    public static function getOrderEvent($orderSide = Order::SIDE_BUY, $orderType = Order::TYPE_LIMIT)
    {
        switch ($orderSide) {
            case Order::SIDE_BUY:
                if ($orderType === Order::TYPE_LIMIT) {
                    return self::LIMIT_BUY_ORDER;
                } elseif ($orderType === Order::TYPE_STOP_LIMIT) {
                    return self::STOP_LIMIT_BUY_ORDER;
                }

                return self::MARKET_BUY_ORDER;

                break;
            case Order::SIDE_SELL:
                if ($orderType === Order::TYPE_LIMIT) {
                    return self::LIMIT_SELL_ORDER;
                } elseif ($orderType === Order::TYPE_STOP_LIMIT) {
                    return self::STOP_LIMIT_SELL_ORDER;
                }

                return self::MARKET_SELL_ORDER;

                break;
        }

        return self::ALL;
    }
}
