<?php

use Webkul\Core\Models\Channel;
use Webkul\Customer\Models\Customer;

use function Pest\Laravel\get;
use function Pest\Laravel\getJson;

// ============================================================================
// Single Channel Store
// ============================================================================

it('shows the only channel name on the order list and the order page', function () {
    expect(Channel::query()->count())->toBe(1);

    $customer = Customer::factory()->create();

    $channel = core()->getDefaultChannel();

    $order = $this->createOrder([
        'channel_name' => null,
        'channel_id' => $channel->id,
        'channel_type' => Channel::class,
    ], customer: $customer);

    $this->loginAsCustomer($customer);

    $records = getJson(route('shop.customers.account.orders.index'), ['X-Requested-With' => 'XMLHttpRequest'])
        ->assertOk()
        ->json('records');

    $response = get(route('shop.customers.account.orders.view', $order->id))
        ->assertOk();

    $visible = preg_replace('/\s+/', ' ', strip_tags($response->getContent()));

    $label = trans('shop::app.customers.account.orders.channel');

    expect(collect($records)->firstWhere('id', $order->id)['channel_name'])->toBe($channel->name)
        ->and($channel->name)->not->toBeEmpty()
        ->and($visible)->toContain($label.': '.$channel->name);
});
