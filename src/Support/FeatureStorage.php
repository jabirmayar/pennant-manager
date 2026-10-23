<?php

namespace daacreators\PennantManager\Support;

use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class FeatureStorage
{
    public static function store(): string
    {
        return config('pennant-manager.store', 'database');
    }

    public static function table(): string
    {
        return config('pennant.stores.' . static::store() . '.table') ?? 'features';
    }

    public static function connectionName(): ?string
    {
        return config('pennant.stores.' . static::store() . '.connection');
    }

    public static function connection(): ConnectionInterface
    {
        return DB::connection(static::connectionName());
    }

    public static function query(): Builder
    {
        return static::connection()->table(static::table());
    }
}
