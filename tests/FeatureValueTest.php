<?php

use daacreators\PennantManager\Facades\PennantManager;
use daacreators\PennantManager\Models\Feature;
use daacreators\PennantManager\Support\FeatureStorage;
use daacreators\PennantManager\Support\FeatureValue;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

it('preserves legacy boolean and envelope formats', function (string $raw, bool $active) {
    expect(FeatureValue::isActive($raw))->toBe($active);
    expect(FeatureValue::resolve($raw))->toBe($active);
})->with([
    ['true', true], ['false', false], ['{"enabled":true}', true], ['{"enabled":false}', false],
]);

it('resolves native Pennant scalar values consistently', function (string $raw, mixed $value) {
    expect(FeatureValue::resolve($raw))->toBe($value);
})->with([['1', 1], ['0', 0], ['"dark"', 'dark'], ['null', false]]);

it('fails closed on invalid values and invalid schedules without throwing', function () {
    expect(FeatureValue::isActive('not-json'))->toBeFalse()
        ->and(FeatureValue::isActive('{"enabled":true,"activate_at":"not-a-date"}'))->toBeFalse();
});

it('honors schedules and preserves rich values and metadata', function () {
    $this->travelTo(now()->startOfDay());
    $raw = FeatureValue::encode(true, 25, now()->addHour()->toDateTimeString(), 'blue', ['source' => 'manual']);
    expect(FeatureValue::isActive($raw))->toBeFalse();
    $this->travel(2)->hours();
    expect(FeatureValue::isActive($raw))->toBeTrue()
        ->and(FeatureValue::resolve($raw))->toBe('blue')
        ->and(FeatureValue::decode($raw)['meta'])->toBe(['source' => 'manual']);
});

it('inherits global values only when no explicit scope row exists', function () {
    config(['pennant-manager.inherit_global' => true]);
    Feature::create(['name' => 'theme', 'scope' => '__laravel_null', 'value' => FeatureValue::encode(true, richValue: 'dark')]);
    expect(FeatureValue::resolveValue('theme', 'User|1'))->toBe('dark');
    Feature::create(['name' => 'theme', 'scope' => 'User|1', 'value' => 'false']);
    expect(FeatureValue::resolveValue('theme', 'User|1'))->toBeFalse()
        ->and(FeatureValue::resolveValue('missing'))->toBeFalse();
});

it('preserves the existing no-fallback behavior by default', function () {
    Feature::create(['name' => 'legacy', 'scope' => '__laravel_null', 'value' => 'true']);
    expect(config('pennant-manager.inherit_global'))->toBeFalse()
        ->and(FeatureValue::resolveValue('legacy', 'User|missing'))->toBeFalse()
        ->and(FeatureValue::resolveValue('legacy'))->toBeTrue();
});

it('uses the configured database store without changing the default table', function () {
    expect(FeatureStorage::table())->toBe('features');
    config(['pennant-manager.store' => 'custom', 'pennant.stores.custom' => [
        'driver' => 'database', 'connection' => 'testing', 'table' => 'custom_features',
    ]]);
    Feature::create(['name' => 'custom', 'scope' => '__laravel_null', 'value' => 'true']);
    expect(FeatureValue::resolveValue('custom'))->toBeTrue()
        ->and(FeatureStorage::query()->count())->toBe(1)
        ->and(DB::table('features')->count())->toBe(0);
});

it('autoloads the documented facade', function () {
    expect(class_exists(PennantManager::class))->toBeTrue();
});

it('keeps writes on the selected store connection', function () {
    config(['database.connections.isolated' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => ''],
        'pennant-manager.store' => 'isolated',
        'pennant.stores.isolated' => ['driver' => 'database', 'connection' => 'isolated', 'table' => 'flags'],
    ]);
    Schema::connection('isolated')->create('flags', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->string('scope');
        $table->text('value');
        $table->timestamps();
    });
    Feature::create(['name' => 'isolated', 'scope' => '__laravel_null', 'value' => 'true']);
    expect(FeatureValue::resolveValue('isolated'))->toBeTrue()
        ->and(DB::connection('testing')->table('features')->count())->toBe(0);
});
