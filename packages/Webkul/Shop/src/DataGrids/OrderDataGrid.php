<?php

namespace Webkul\Shop\DataGrids;

use Illuminate\Support\Facades\DB;
use Webkul\Core\Models\ChannelProxy;
use Webkul\DataGrid\DataGrid;
use Webkul\Sales\Models\Order;
use Webkul\Shop\Support\OrderChannelName;

class OrderDataGrid extends DataGrid
{
    /**
     * Prepare query builder.
     */
    public function prepareQueryBuilder()
    {
        $locale = app()->getLocale();

        $fallbackLocale = config('translatable.fallback_locale');

        $includeFallbackLocale = is_string($fallbackLocale)
            && $fallbackLocale !== ''
            && $fallbackLocale !== $locale;

        $channelName = $this->channelNameExpression($locale, $includeFallbackLocale ? $fallbackLocale : null);

        $queryBuilder = DB::table('orders')
            ->addSelect(
                'orders.id',
                'orders.increment_id',
                DB::raw($channelName.' as channel_name'),
                'orders.status',
                'orders.created_at',
                'orders.grand_total',
                'orders.order_currency_code'
            )
            ->where('orders.customer_id', auth()->guard('customer')->user()->id);

        $this->addFilter('channel_name', DB::raw($channelName));

        return $queryBuilder;
    }

    /**
     * Add columns.
     *
     * @return void
     */
    public function prepareColumns()
    {
        $this->addColumn([
            'index' => 'increment_id',
            'label' => trans('shop::app.customers.account.orders.order-id'),
            'type' => 'string',
            'searchable' => true,
            'filterable' => true,
            'sortable' => true,
        ]);

        $this->addColumn([
            'index' => 'channel_name',
            'label' => trans('shop::app.customers.account.orders.channel'),
            'type' => 'string',
            'searchable' => true,
            'filterable' => true,
            'sortable' => true,
        ]);

        $this->addColumn([
            'index' => 'created_at',
            'label' => trans('shop::app.customers.account.orders.order-date'),
            'type' => 'date',
            'searchable' => true,
            'filterable' => true,
            'filterable_type' => 'date_range',
            'sortable' => true,
        ]);

        $this->addColumn([
            'index' => 'grand_total',
            'label' => trans('shop::app.customers.account.orders.total'),
            'type' => 'decimal',
            'searchable' => true,
            'filterable' => true,
            'sortable' => true,
            'closure' => function ($row) {
                return core()->formatPrice($row->grand_total, $row->order_currency_code);
            },
        ]);

        $this->addColumn([
            'index' => 'status',
            'label' => trans('shop::app.customers.account.orders.status.title'),
            'type' => 'string',
            'searchable' => true,
            'filterable' => true,
            'filterable_type' => 'dropdown',
            'filterable_options' => [
                [
                    'label' => trans('shop::app.customers.account.orders.status.options.processing'),
                    'value' => Order::STATUS_PROCESSING,
                ],
                [
                    'label' => trans('shop::app.customers.account.orders.status.options.completed'),
                    'value' => Order::STATUS_COMPLETED,
                ],
                [
                    'label' => trans('shop::app.customers.account.orders.status.options.canceled'),
                    'value' => Order::STATUS_CANCELED,
                ],
                [
                    'label' => trans('shop::app.customers.account.orders.status.options.closed'),
                    'value' => Order::STATUS_CLOSED,
                ],
                [
                    'label' => trans('shop::app.customers.account.orders.status.options.pending'),
                    'value' => Order::STATUS_PENDING,
                ],
                [
                    'label' => trans('shop::app.customers.account.orders.status.options.pending-payment'),
                    'value' => Order::STATUS_PENDING_PAYMENT,
                ],
                [
                    'label' => trans('shop::app.customers.account.orders.status.options.fraud'),
                    'value' => Order::STATUS_FRAUD,
                ],
            ],
            'sortable' => true,
            'closure' => function ($row) {
                switch ($row->status) {
                    case Order::STATUS_PROCESSING:
                        return '<p class="label-processing">'.trans('shop::app.customers.account.orders.status.options.processing').'</p>';

                    case Order::STATUS_COMPLETED:
                        return '<p class="label-active">'.trans('shop::app.customers.account.orders.status.options.completed').'</p>';

                    case Order::STATUS_CANCELED:
                        return '<p class="label-canceled">'.trans('shop::app.customers.account.orders.status.options.canceled').'</p>';

                    case Order::STATUS_CLOSED:
                        return '<p class="label-closed">'.trans('shop::app.customers.account.orders.status.options.closed').'</p>';

                    case Order::STATUS_PENDING:
                        return '<p class="label-pending">'.trans('shop::app.customers.account.orders.status.options.pending').'</p>';

                    case Order::STATUS_PENDING_PAYMENT:
                        return '<p class="label-pending">'.trans('shop::app.customers.account.orders.status.options.pending-payment').'</p>';

                    case Order::STATUS_FRAUD:
                        return '<p class="label-canceled">'.trans('shop::app.customers.account.orders.status.options.fraud').'</p>';
                }
            },
        ]);
    }

    /**
     * Prepare actions.
     *
     * @return void
     */
    public function prepareActions()
    {
        $this->addAction([
            'icon' => 'icon-eye',
            'title' => trans('shop::app.customers.account.orders.action-view'),
            'method' => 'GET',
            'url' => function ($row) {
                return route('shop.customers.account.orders.view', $row->id);
            },
        ]);
    }

    /**
     * SQL expression for the channel name: stored name, channel translation, then the default channel.
     */
    private function channelNameExpression(string $locale, ?string $fallbackLocale): string
    {
        $prefix = DB::getTablePrefix();

        $names = [
            'NULLIF('.$prefix.'orders.channel_name, \'\')',
            $this->translatedChannelName($prefix, $locale),
        ];

        if ($fallbackLocale !== null) {
            $names[] = $this->translatedChannelName($prefix, $fallbackLocale);
        }

        $names[] = $this->quote(OrderChannelName::fallback());

        return 'COALESCE('.implode(', ', $names).')';
    }

    /**
     * Channel translation for one locale when the order's channel record is that channel.
     */
    private function translatedChannelName(string $prefix, string $locale): string
    {
        return 'NULLIF((SELECT '.$prefix.'channel_translations.name'
            .' FROM '.$prefix.'channel_translations'
            .' WHERE '.$prefix.'channel_translations.channel_id = '.$prefix.'orders.channel_id'
            .' AND '.$prefix.'orders.channel_type = '.$this->quote(ChannelProxy::modelClass())
            .' AND '.$prefix.'channel_translations.locale = '.$this->quote($locale)
            .' LIMIT 1), \'\')';
    }

    /**
     * Quote a value so it can be embedded in a channel-name expression.
     */
    private function quote(string $value): string
    {
        return DB::connection()->escape($value);
    }
}
