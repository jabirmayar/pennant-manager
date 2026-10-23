<?php

namespace daacreators\PennantManager\Support;

use Illuminate\Database\Eloquent\Model;
use Laravel\Pennant\Feature;

class ScopeConfig
{
    public static function modelClass(string $type): ?string
    {
        $entry = config("pennant-manager.scope_models.{$type}");

        $model = is_string($entry) ? $entry : (is_array($entry) ? ($entry['model'] ?? null) : null);

        return is_string($model) && is_subclass_of($model, Model::class) ? $model : null;
    }

    public static function scopeKey(Model $model): string
    {
        return Feature::serializeScope($model);
    }

    public static function scopePrefix(string $modelClass): string
    {
        $model = new $modelClass;
        // A numeric sentinel survives Eloquent's default integer primary-key cast.
        $model->setAttribute($model->getKeyName(), 1);

        return substr(static::scopeKey($model), 0, -strlen((string) $model->getKey()));
    }

    public static function searchColumn(string $type): string
    {
        $entry = config("pennant-manager.scope_models.{$type}");

        if (is_array($entry) && isset($entry['search_column'])) {
            return $entry['search_column'];
        }

        if (is_string($entry)) {
            return config('pennant-manager.scope_search_column', 'email');
        }

        return 'id';
    }

    public static function labelColumn(string $type): string
    {
        $entry = config("pennant-manager.scope_models.{$type}");

        if (is_array($entry) && isset($entry['label_column'])) {
            return $entry['label_column'];
        }

        // fall back to search column so the list still shows something readable
        return static::searchColumn($type);
    }

    public static function segmentColumns(string $type): array
    {
        $entry = config("pennant-manager.scope_models.{$type}");

        if (is_array($entry) && isset($entry['segment_columns'])) {
            return $entry['segment_columns'];
        }

        return [];
    }

    public static function modelOptions(): array
    {
        return array_combine(
            array_keys(config('pennant-manager.scope_models', [])),
            array_keys(config('pennant-manager.scope_models', []))
        );
    }
}
