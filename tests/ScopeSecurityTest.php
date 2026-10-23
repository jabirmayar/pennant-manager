<?php

use daacreators\PennantManager\Models\Feature;
use daacreators\PennantManager\Resources\FeatureResource;
use daacreators\PennantManager\Resources\FeatureResource\Pages\ListFeatures;
use daacreators\PennantManager\Resources\FeatureResource\Pages\ManageScopes;
use daacreators\PennantManager\Support\FeatureStorage;
use daacreators\PennantManager\Tests\Models\ScopePerson;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;

beforeEach(function () {
    Filament::setCurrentPanel(Filament::getPanel('test'));
    config(['auth.providers.users.model' => ScopePerson::class, 'pennant-manager.scope_models' => [
        'Person' => ['model' => ScopePerson::class, 'search_column' => 'email', 'label_column' => 'name', 'segment_columns' => ['plan']],
    ]]);
    $this->person = ScopePerson::create(['uuid' => 'custom-key', 'name' => 'Test', 'email' => 'test@example.com', 'plan' => '0', 'secret' => 'private']);
    $this->actingAs($this->person);
    $this->record = Feature::create(['name' => 'managed', 'scope' => '__laravel_null', 'value' => 'false']);
});

it('supports the existing scope activation form with a custom primary key', function () {
    Livewire::test(ManageScopes::class, ['record' => $this->record->id])
        ->fillForm(['scope_type' => 'Person', 'scope_ids' => ['custom-key']])
        ->call('activateScope')->assertHasNoFormErrors();
    expect(FeatureStorage::query()->where('scope', ScopePerson::class . '|custom-key')->exists())->toBeTrue();
});

it('cannot delete an override belonging to another feature', function () {
    $other = Feature::create(['name' => 'other', 'scope' => ScopePerson::class . '|custom-key', 'value' => 'true']);
    Livewire::test(ManageScopes::class, ['record' => $this->record->id])->call('removeScope', $other->id)->assertNotFound();
    expect(Feature::find($other->id))->not->toBeNull();
});

it('cannot delete the global feature through scope removal', function () {
    Livewire::test(ManageScopes::class, ['record' => $this->record->id])->call('removeScope', $this->record->id)->assertNotFound();
    expect(Feature::find($this->record->id))->not->toBeNull();
});

it('enforces segment allowlists on the server', function () {
    Livewire::test(ManageScopes::class, ['record' => $this->record->id])
        ->fillForm(['scope_type' => 'Person', 'segment_column_select' => 'secret', 'segment_value' => 'private'])
        ->call('activateBySegment');
    expect(FeatureStorage::query()->where('scope', '!=', '__laravel_null')->count())->toBe(0);
});

it('accepts the zero string as a valid segment value', function () {
    Livewire::test(ManageScopes::class, ['record' => $this->record->id])
        ->fillForm(['scope_type' => 'Person', 'segment_column_select' => 'plan', 'segment_value' => '0'])
        ->call('activateBySegment')->assertHasNoFormErrors();
    expect(FeatureStorage::query()->where('scope', ScopePerson::class . '|custom-key')->exists())->toBeTrue();
});

it('honors host feature policies for navigation and scope writes', function () {
    Gate::policy(Feature::class, DenyFeaturePolicy::class);
    expect(FeatureResource::canAccess())->toBeFalse();
    Livewire::test(ManageScopes::class, ['record' => $this->record->id])->assertForbidden();
});

it('rechecks update policies on an already mounted scope page', function () {
    $page = Livewire::test(ManageScopes::class, ['record' => $this->record->id]);
    Gate::policy(Feature::class, ReadOnlyFeaturePolicy::class);
    $page->call('activateScope')->assertForbidden();
    expect(FeatureStorage::query()->where('scope', '!=', '__laravel_null')->count())->toBe(0);
});

it('does not overwrite a feature when creating a duplicate name', function () {
    Livewire::test(ListFeatures::class)
        ->callAction('createFeature', data: ['name' => 'managed'])->assertHasNoActionErrors();
    expect(Feature::where('name', 'managed')->count())->toBe(1)
        ->and($this->record->fresh()->value)->toBe('false');
});

it('deletes from the selected store without purging the default store', function () {
    config(['pennant-manager.store' => 'custom', 'pennant.stores.custom' => [
        'driver' => 'database', 'connection' => 'testing', 'table' => 'custom_features',
    ]]);
    $custom = Feature::create(['name' => 'managed', 'scope' => '__laravel_null', 'value' => 'true']);
    Livewire::test(ListFeatures::class)->callTableAction('delete', $custom)->assertHasNoTableActionErrors();
    expect(DB::table('features')->where('name', 'managed')->exists())->toBeTrue()
        ->and(FeatureStorage::query()->where('name', 'managed')->exists())->toBeFalse();
});

class ReadOnlyFeaturePolicy
{
    public function viewAny($user): bool
    {
        return true;
    }

    public function update($user, $feature): bool
    {
        return false;
    }
}

class DenyFeaturePolicy
{
    public function viewAny($user): bool
    {
        return false;
    }

    public function update($user, $feature): bool
    {
        return false;
    }
}
