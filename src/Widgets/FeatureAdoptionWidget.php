<?php

namespace daacreators\PennantManager\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class FeatureAdoptionWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $scopeModels = config('pennant-manager.scope_models', []);
        
        $features = DB::table('features')
            ->where('scope', '!=', '__laravel_null')
            ->whereRaw("(value = 'true' OR (value LIKE '{%' AND JSON_EXTRACT(value, '$.enabled') = true))")
            ->get();

        if ($features->isEmpty()) {
            return [
                Stat::make('Adoption Stats', 'No data')
                    ->description('Assign features to scopes to see metrics')
                    ->descriptionIcon('heroicon-m-chart-bar')
                    ->color('gray'),
            ];
        }

        // Extract model classes from the nested config
        $modelCounts = [];
        foreach ($scopeModels as $modelKey => $modelConfig) {
            $modelClass = is_array($modelConfig) ? $modelConfig['model'] : $modelConfig;
            $modelCounts[$modelClass] = $modelClass::count() ?: 1;
        }

        $grouped = [];
        foreach ($features as $feature) {
            $parts = explode('|', $feature->scope);
            if (count($parts) === 2) {
                $modelClass = $parts[0];
                
                if (!isset($grouped[$feature->name][$modelClass])) {
                    $grouped[$feature->name][$modelClass] = 0;
                }
                
                $grouped[$feature->name][$modelClass]++;
            }
        }

        $stats = [];
        foreach ($grouped as $featureName => $models) {
            if (!isset($modelCounts[$modelClass])) continue; 
            foreach ($models as $modelClass => $activeCount) {
                $total = $modelCounts[$modelClass] ?? 1;
                $percentage = round(($activeCount / $total) * 100, 1);
                
                $modelName = class_basename($modelClass);
                
                $stats[] = Stat::make("{$featureName} ({$modelName}s)", "{$percentage}%")
                    ->description("Active for {$activeCount} {$modelName}s")
                    ->descriptionIcon('heroicon-m-chart-pie')
                    ->color('info');
            }
        }

        return $stats;
    }
}