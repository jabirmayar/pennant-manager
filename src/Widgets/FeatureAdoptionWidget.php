<?php

namespace daacreators\PennantManager\Widgets;

use daacreators\PennantManager\Resources\FeatureResource;
use daacreators\PennantManager\Support\FeatureStorage;
use daacreators\PennantManager\Support\FeatureValue;
use daacreators\PennantManager\Support\ScopeConfig;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class FeatureAdoptionWidget extends BaseWidget
{
    public static function canView(): bool
    {
        return FeatureResource::canAccess();
    }

    protected function getStats(): array
    {
        $models = [];
        foreach (ScopeConfig::modelOptions() as $type) {
            if ($model = ScopeConfig::modelClass($type)) {
                $models[$model] = ScopeConfig::scopePrefix($model);
            }
        }
        $grouped = [];
        foreach (FeatureStorage::query()->where('scope', '!=', '__laravel_null')->cursor() as $feature) {
            if (! FeatureValue::isActive($feature->value)) {
                continue;
            }
            foreach ($models as $model => $prefix) {
                if (str_starts_with($feature->scope, $prefix)) {
                    $grouped[$feature->name][$model][] = substr($feature->scope, strlen($prefix));

                    break;
                }
            }
        }
        $stats = [];
        $totals = [];
        foreach ($grouped as $name => $scopes) {
            foreach ($scopes as $model => $ids) {
                $total = $totals[$model] ??= $model::count();
                $active = $model::whereKey($ids)->count();
                if (! $total || ! $active) {
                    continue;
                }
                $label = class_basename($model);
                $stats[] = Stat::make("{$name} ({$label}s)", round($active / $total * 100, 1) . '%')
                    ->description("Active for {$active} {$label}s")
                    ->descriptionIcon('heroicon-m-chart-pie')->color('info');
            }
        }

        return $stats ?: [Stat::make('Adoption Stats', 'No data')
            ->description('Assign features to scopes to see metrics')
            ->descriptionIcon('heroicon-m-chart-bar')->color('gray')];
    }
}
