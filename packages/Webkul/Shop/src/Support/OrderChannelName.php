<?php

namespace Webkul\Shop\Support;

use Webkul\Sales\Models\Order;

class OrderChannelName
{
    /**
     * Channel name shown for an order: the stored name, then the channel, then the default channel.
     */
    public static function forOrder(Order $order): string
    {
        $name = $order->channel_name ?: $order->channel?->name;

        return $name ?: self::fallback();
    }

    /**
     * Name shown when an order has no channel name and no channel record.
     */
    public static function fallback(): string
    {
        $name = core()->getDefaultChannel()?->name;

        return $name ?: trans('shop::app.customers.account.orders.default-channel');
    }
}
