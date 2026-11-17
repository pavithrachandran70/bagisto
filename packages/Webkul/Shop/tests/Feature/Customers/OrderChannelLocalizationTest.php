<?php

use Illuminate\Support\Facades\File;
use Webkul\Core\Models\Locale;

use function Pest\Laravel\get;
use function Pest\Laravel\getJson;

// ============================================================================
// Channel Label Translations
// ============================================================================

it('defines a channel label and a fallback name in every shipped locale', function () {
    $path = base_path('packages/Webkul/Shop/src/Resources/lang');

    $english = require $path.'/en/app.php';

    $englishOrders = $english['customers']['account']['orders'];

    $directories = File::directories($path);

    expect($directories)->not->toBeEmpty();

    foreach ($directories as $directory) {
        $orders = (require $directory.'/app.php')['customers']['account']['orders'];

        expect($orders['channel'])->toBeString()->not->toBeEmpty()
            ->and($orders['default-channel'])->toBeString()->not->toBeEmpty();

        if (basename($directory) === 'en') {
            continue;
        }

        expect($orders['channel'])->not->toBe($englishOrders['channel'])
            ->and($orders['default-channel'])->not->toBe($englishOrders['default-channel']);
    }
});

it('shows the translated channel label on the order list', function (string $locale, string $label) {
    $localeModel = Locale::query()->firstWhere('code', $locale)
        ?? Locale::factory()->create(['code' => $locale]);

    $channel = core()->getCurrentChannel();

    $channel->locales()->syncWithoutDetaching([$localeModel->id]);

    $channel->unsetRelation('locales');

    $this->loginAsCustomer();

    get(route('shop.customers.account.orders.index', ['locale' => $locale]))
        ->assertOk()
        ->assertSeeText($label);

    $columns = getJson(route('shop.customers.account.orders.index', ['locale' => $locale]), ['X-Requested-With' => 'XMLHttpRequest'])
        ->assertOk()
        ->json('columns');

    expect(collect($columns)->firstWhere('index', 'channel_name')['label'])->toBe($label);
})->with([
    'english' => ['en', 'Channel'],
    'arabic' => ['ar', 'القناة'],
    'german' => ['de', 'Kanal'],
    'spanish' => ['es', 'Canal'],
    'french' => ['fr', 'Canal'],
    'hindi' => ['hi_IN', 'चैनल'],
]);
