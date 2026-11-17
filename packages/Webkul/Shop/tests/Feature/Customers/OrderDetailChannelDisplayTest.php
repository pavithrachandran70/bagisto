<?php

use Webkul\Core\Models\Channel;
use Webkul\Customer\Models\Customer;

use function Pest\Laravel\get;
use function Pest\Laravel\getJson;

// ============================================================================
// Order Detail Channel Name
// ============================================================================

it('shows the same channel name on the order detail page as on the list', function () {
    $customer = Customer::factory()->create();

    $channel = Channel::factory()->create();

    $channel->translations()->update(['name' => 'Wholesale Desk']);

    $order = $this->createOrder([
        'channel_name' => null,
        'channel_id' => $channel->id,
        'channel_type' => Channel::class,
    ], customer: $customer);

    $this->loginAsCustomer($customer);

    $records = getJson(route('shop.customers.account.orders.index'), ['X-Requested-With' => 'XMLHttpRequest'])
        ->assertOk()
        ->json('records');

    $listedName = collect($records)->firstWhere('id', $order->id)['channel_name'];

    $response = get(route('shop.customers.account.orders.view', $order->id))
        ->assertOk()
        ->assertSeeText(trans('shop::app.customers.account.orders.channel'));

    $visible = preg_replace('/\s+/', ' ', strip_tags($response->getContent()));

    expect($listedName)->toBe('Wholesale Desk')
        ->and($visible)->toContain(trans('shop::app.customers.account.orders.channel').': '.$listedName);
});
