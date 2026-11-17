<?php

use Webkul\Customer\Models\Customer;

use function Pest\Laravel\get;
use function Pest\Laravel\getJson;

// ============================================================================
// Long Channel Names
// ============================================================================

it('keeps a long channel name in the order list and on the order page', function () {
    $customer = Customer::factory()->create();

    $channelName = str_repeat('Northwind ', 12);

    $order = $this->createOrder([
        'channel_name' => $channelName,
    ], customer: $customer);

    $this->loginAsCustomer($customer);

    $records = getJson(route('shop.customers.account.orders.index'), ['X-Requested-With' => 'XMLHttpRequest'])
        ->assertOk()
        ->json('records');

    $list = get(route('shop.customers.account.orders.index'))
        ->assertOk();

    $detail = get(route('shop.customers.account.orders.view', $order->id))
        ->assertOk();

    $visible = preg_replace('/\s+/', ' ', strip_tags($detail->getContent()));

    expect(mb_strlen($channelName))->toBeGreaterThan(100)
        ->and(collect($records)->firstWhere('id', $order->id)['channel_name'])->toBe($channelName)
        ->and($list->getContent())->toContain('wrap-anywhere')
        ->and($detail->getContent())->toContain('wrap-anywhere')
        ->and($visible)->toContain(trans('shop::app.customers.account.orders.channel').': '.$channelName);
});
