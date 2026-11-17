<?php

use Webkul\Customer\Models\Customer;
use Webkul\Shop\Support\OrderChannelName;

use function Pest\Laravel\get;
use function Pest\Laravel\getJson;

// ============================================================================
// Missing Channel Fallback
// ============================================================================

it('shows a channel name when the order has no channel name and no channel', function () {
    $customer = Customer::factory()->create();

    $order = $this->createOrder([
        'channel_name' => null,
        'channel_id' => null,
        'channel_type' => null,
    ], customer: $customer);

    $this->loginAsCustomer($customer);

    $fallback = OrderChannelName::fallback();

    $records = getJson(route('shop.customers.account.orders.index'), ['X-Requested-With' => 'XMLHttpRequest'])
        ->assertOk()
        ->json('records');

    $response = get(route('shop.customers.account.orders.view', $order->id))
        ->assertOk()
        ->assertSeeText(trans('shop::app.customers.account.orders.channel'));

    $visible = preg_replace('/\s+/', ' ', strip_tags($response->getContent()));

    expect($fallback)->not->toBeEmpty()
        ->and(collect($records)->firstWhere('id', $order->id)['channel_name'])->toBe($fallback)
        ->and($visible)->toContain(trans('shop::app.customers.account.orders.channel').': '.$fallback);
});
