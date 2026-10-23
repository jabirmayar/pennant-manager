<?php

namespace daacreators\PennantManager\Services;

use daacreators\PennantManager\Support\FeatureStorage;
use daacreators\PennantManager\Support\FeatureValue;
use daacreators\PennantManager\Support\ScopeConfig;
use Illuminate\Validation\ValidationException;

class ScopeRollout
{
    public static function apply(string $feature, string $type, int $percentage): int
    {
        $modelClass = ScopeConfig::modelClass($type);
        if (! $modelClass || $percentage < 1 || $percentage > 100) {
            throw ValidationException::withMessages(['scope_type' => 'Choose a configured scope model and a percentage from 1 to 100.']);
        }

        return FeatureStorage::connection()->transaction(function () use ($feature, $modelClass, $percentage) {
            // Serialize rollouts for this feature and preserve concurrent explicit overrides.
            FeatureStorage::query()->where('name', $feature)->where('scope', '__laravel_null')->lockForUpdate()->first();
            $prefix = ScopeConfig::scopePrefix($modelClass);
            $existing = FeatureStorage::query()->where('name', $feature)->get()
                ->filter(fn ($row) => str_starts_with($row->scope, $prefix));
            $existingIds = $existing->map(fn ($row) => substr($row->scope, strlen($prefix)))->all();
            $activeIds = $existing->filter(fn ($row) => FeatureValue::isActive($row->value))
                ->map(fn ($row) => substr($row->scope, strlen($prefix)))->all();
            $model = new $modelClass;
            $activeCount = $modelClass::whereKey($activeIds)->count();
            $target = (int) ceil($modelClass::count() * $percentage / 100);
            $needed = max(0, $target - $activeCount);
            $records = $modelClass::whereNotIn($model->getKeyName(), $existingIds)
                ->inRandomOrder()->limit($needed)->get();
            $added = 0;
            foreach ($records as $record) {
                $added += FeatureStorage::query()->insertOrIgnore([
                    'name' => $feature,
                    'scope' => ScopeConfig::scopeKey($record),
                    'value' => FeatureValue::encode(true, meta: [
                        'source' => 'rollout', 'percentage' => $percentage,
                        'activated_at' => now()->toDateTimeString(),
                    ]),
                    'created_at' => now(), 'updated_at' => now(),
                ]);
            }

            return $added;
        });
    }
}
