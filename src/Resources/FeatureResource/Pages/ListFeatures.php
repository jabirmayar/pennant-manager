<?php

namespace daacreators\PennantManager\Resources\FeatureResource\Pages;

use daacreators\PennantManager\Resources\FeatureResource;
use daacreators\PennantManager\Services\FeatureDiscovery;
use daacreators\PennantManager\Support\FeatureStorage;
use daacreators\PennantManager\Support\FeatureValue;
use daacreators\PennantManager\Widgets\FeatureAdoptionWidget;
use Filament\Actions\Action;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Laravel\Pennant\Feature;
use Livewire\Component;

class ListFeatures extends ListRecords
{
    protected static string $resource = FeatureResource::class;

    protected function getHeaderWidgets(): array
    {
        return [
            FeatureAdoptionWidget::class,
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('discoverFeatures')
                ->authorize(fn () => FeatureResource::canCreate())
                ->label('Discover Features')
                ->icon('heroicon-o-magnifying-glass')
                ->color('info')
                ->form([
                    CheckboxList::make('features_to_import')
                        ->label('Found features not in database:')
                        ->options(function () {
                            $defined = FeatureDiscovery::discover();
                            $inDb = FeatureStorage::query()->pluck('name')->toArray();
                            $missing = array_diff($defined, $inDb);

                            return array_combine($missing, $missing);
                        })
                        ->default(function () {
                            $defined = FeatureDiscovery::discover();
                            $inDb = FeatureStorage::query()->pluck('name')->toArray();

                            return array_values(array_diff($defined, $inDb));
                        })
                        ->visible(function () {
                            $configFeatures = FeatureDiscovery::discover();
                            $dbFeatures = FeatureStorage::query()->pluck('name')->toArray();

                            return count(array_diff($configFeatures, $dbFeatures)) > 0;
                        }),

                    Placeholder::make('all_good')
                        ->label('Up to date!')
                        ->content('All features defined in your config are already in the database.')
                        ->visible(function () {
                            $configFeatures = FeatureDiscovery::discover();
                            $dbFeatures = FeatureStorage::query()->pluck('name')->toArray();

                            return count(array_diff($configFeatures, $dbFeatures)) === 0;
                        }),
                ])
                ->action(function (array $data, Component $livewire) {
                    if (empty($data['features_to_import'])) {
                        return;
                    }

                    foreach ($data['features_to_import'] as $featureName) {
                        FeatureStorage::query()->insertOrIgnore([
                            'name' => $featureName, 'scope' => '__laravel_null',
                            'value' => FeatureValue::encode(enabled: false),
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }

                    Feature::flushCache();

                    Notification::make()
                        ->title('Features Imported Successfully!')
                        ->success()
                        ->send();

                    $livewire->dispatch('$refresh');
                })
                ->modalWidth('md'),

            Action::make('createFeature')
                ->authorize(fn () => FeatureResource::canCreate())
                ->label('Create Feature')
                ->icon('heroicon-o-plus')
                ->form([
                    TextInput::make('name')
                        ->label('Feature Name')
                        ->placeholder('e.g., new-dashboard')
                        ->maxLength(255)
                        ->required(),
                ])
                ->action(function (array $data, Component $livewire) {
                    $created = FeatureStorage::query()->insertOrIgnore([
                        'name' => $data['name'],
                        'scope' => '__laravel_null',
                        'value' => FeatureValue::encode(enabled: true),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    if (! $created) {
                        Notification::make()->title('This feature already exists')->warning()->send();

                        return;
                    }
                    Feature::flushCache();

                    Notification::make()
                        ->title('Created!')
                        ->success()
                        ->send();

                    $livewire->dispatch('$refresh');
                })
                ->modalWidth('md')
                ->closeModalByClickingAway(false),
        ];
    }
}
