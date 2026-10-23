<?php

use daacreators\PennantManager\Models\Feature;
use daacreators\PennantManager\Services\ScopeRollout;
use daacreators\PennantManager\Support\FeatureStorage;
use daacreators\PennantManager\Support\FeatureValue;
use daacreators\PennantManager\Support\ScopeConfig;
use daacreators\PennantManager\Tests\Models\IntegerPerson;
use daacreators\PennantManager\Tests\Models\ScopePerson;
use daacreators\PennantManager\Tests\Models\ScopeTeam;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Validation\ValidationException;
use Laravel\Pennant\Feature as Pennant;

beforeEach(function () {
    config(['pennant-manager.scope_models' => ['Person' => ScopePerson::class, 'Team' => ['model' => ScopeTeam::class]]]);
    foreach (range(1, 4) as $id) {
        ScopePerson::create(['uuid' => (string) $id, 'email' => "person{$id}@example.com"]);
    }
    ScopeTeam::create(['uuid' => '1', 'name' => 'Same identifier']);
    Feature::create(['name' => 'rollout', 'scope' => '__laravel_null', 'value' => 'false']);
});

afterEach(function () {
    Relation::morphMap([], false);
});

it('supports legacy and extended model configuration', function () {
    expect(ScopeConfig::modelClass('Person'))->toBe(ScopePerson::class)
        ->and(ScopeConfig::modelClass('Team'))->toBe(ScopeTeam::class)
        ->and(ScopeConfig::searchColumn('Person'))->toBe('email');
});

it('never excludes a person because another model has the same identifier', function () {
    Feature::create(['name' => 'rollout', 'scope' => ScopeTeam::class . '|1', 'value' => 'false']);
    expect(ScopeRollout::apply('rollout', 'Person', 100))->toBe(4);
    expect(FeatureStorage::query()->where('scope', ScopePerson::class . '|1')->exists())->toBeTrue();
});

it('does not overwrite explicit overrides and repeated rollouts do not expand the target', function () {
    Feature::create(['name' => 'rollout', 'scope' => ScopePerson::class . '|1', 'value' => 'false']);
    expect(ScopeRollout::apply('rollout', 'Person', 50))->toBe(2)
        ->and(ScopeRollout::apply('rollout', 'Person', 50))->toBe(0);
    expect(FeatureStorage::query()->where('scope', ScopePerson::class . '|1')->value('value'))->toBe('false');
});

it('uses Pennant morph serialization for new scopes', function () {
    Relation::morphMap(['person' => ScopePerson::class]);
    Pennant::useMorphMap();
    expect(ScopeRollout::apply('rollout', 'Person', 25))->toBe(1);
    expect(FeatureStorage::query()->where('scope', 'like', 'person|%')->count())->toBe(1);
});

it('rejects unknown models and invalid percentages', function () {
    expect(fn () => ScopeRollout::apply('rollout', 'unknown', 50))->toThrow(ValidationException::class);
    expect(fn () => ScopeRollout::apply('rollout', 'Person', 101))->toThrow(ValidationException::class);
});

it('preserves integer-keyed model behavior as well as custom string keys', function () {
    $model = IntegerPerson::class;
    config(['pennant-manager.scope_models.IntegerPerson' => $model]);
    foreach (range(1, 4) as $id) {
        $model::create(['id' => $id]);
    }
    expect(ScopeConfig::scopePrefix($model))->toBe($model . '|')
        ->and(ScopeRollout::apply('rollout', 'IntegerPerson', 50))->toBe(2)
        ->and(ScopeRollout::apply('rollout', 'IntegerPerson', 50))->toBe(0);
});

it('activates due schedules once and flushes cached values', function () {
    FeatureStorage::query()->where('name', 'rollout')->update(['value' => FeatureValue::encode(false, activateAt: now()->subMinute()->toDateTimeString())]);
    Pennant::define('rollout', false);
    expect(Pennant::for(null)->value('rollout')['enabled'])->toBeFalse();
    $this->artisan('pennant:activate-scheduled')->expectsOutput('Activated 1 scheduled feature(s).')->assertSuccessful();
    expect(FeatureValue::resolveValue('rollout'))->toBeTrue();
    expect(Pennant::for(null)->value('rollout')['enabled'])->toBeTrue();
    $this->artisan('pennant:activate-scheduled')->expectsOutput('Activated 0 scheduled feature(s).')->assertSuccessful();
});

it('skips broken schedules while continuing valid work', function () {
    FeatureStorage::query()->where('name', 'rollout')->update(['value' => FeatureValue::encode(false, activateAt: 'not-a-date')]);
    Feature::create(['name' => 'valid', 'scope' => '__laravel_null', 'value' => FeatureValue::encode(false, activateAt: now()->subMinute()->toDateTimeString())]);
    $this->artisan('pennant:activate-scheduled')->assertSuccessful();
    expect(FeatureValue::resolveValue('valid'))->toBeTrue()->and(FeatureValue::resolveValue('rollout'))->toBeFalse();
});
