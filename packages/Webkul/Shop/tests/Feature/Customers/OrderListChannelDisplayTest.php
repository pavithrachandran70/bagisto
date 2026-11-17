<?php

use Webkul\Core\Models\Channel;
use Webkul\Customer\Models\Customer;

use function Pest\Laravel\get;
use function Pest\Laravel\getJson;

// ============================================================================
// Order List Channel Name
// ============================================================================

it('shows each order channel name beside the order in the list', function () {
    $customer = Customer::factory()->create();

    $retail = Channel::factory()->create();

    $retail->translations()->update(['name' => 'Retail Counter']);

    $outlet = Channel::factory()->create();

    $outlet->translations()->update(['name' => 'Outlet Shop']);

    $retailOrder = $this->createOrder([
        'channel_name' => 'Retail Counter',
        'channel_id' => $retail->id,
        'channel_type' => Channel::class,
    ], customer: $customer);

    $outletOrder = $this->createOrder([
        'channel_name' => null,
        'channel_id' => $outlet->id,
        'channel_type' => Channel::class,
    ], customer: $customer);

    $this->loginAsCustomer($customer);

    get(route('shop.customers.account.orders.index'))
        ->assertOk()
        ->assertSeeText(trans('shop::app.customers.account.orders.channel'));

    $records = collect(getJson(route('shop.customers.account.orders.index'), ['X-Requested-With' => 'XMLHttpRequest'])
        ->assertOk()
        ->json('records'));

    expect($records->firstWhere('id', $retailOrder->id)['channel_name'])->toBe('Retail Counter')
        ->and($records->firstWhere('id', $outletOrder->id)['channel_name'])->toBe('Outlet Shop');
});
