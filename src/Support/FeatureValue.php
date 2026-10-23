<?php

namespace daacreators\PennantManager\Support;

use Carbon\Carbon;

class FeatureValue
{
    public static function isActive(string $raw): bool
    {
        $decoded = static::decode($raw);

        if (! empty($decoded['activate_at'])) {
            try {
                $scheduled = Carbon::parse($decoded['activate_at']);
            } catch (\Throwable) {
                return false;
            }
            if (now()->lt($scheduled)) {
                return false;
            }
        }

        return (bool) ($decoded['enabled'] ?? false);
    }

    public static function encode(
        bool $enabled,
        ?int $percentage = null,
        ?string $activateAt = null,
        mixed $richValue = null,
        ?array $meta = null,
    ): string {
        $data = ['enabled' => $enabled];

        if ($percentage !== null) {
            $data['percentage'] = $percentage;
        }
        if ($activateAt !== null) {
            $data['activate_at'] = $activateAt;
        }
        if ($richValue !== null) {
            $data['rich_value'] = $richValue;
        }
        if ($meta !== null) {
            $data['meta'] = $meta;
        }

        return json_encode($data);
    }

    public static function decode(string $raw): array
    {
        $decoded = json_decode($raw, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return ['enabled' => false];
        }

        if (! is_array($decoded)) {
            return is_bool($decoded) || $decoded === null
                ? ['enabled' => (bool) $decoded]
                : ['enabled' => (bool) $decoded, 'rich_value' => $decoded];
        }

        return $decoded;
    }

    public static function resolve(string $raw): mixed
    {
        $data = static::decode($raw);

        return $data['rich_value'] ?? ($data['enabled'] ?? false);
    }

    public static function resolveValue(string $featureName, string $scope = '__laravel_null'): mixed
    {
        $raw = FeatureStorage::query()
            ->where('name', $featureName)
            ->where('scope', $scope)
            ->value('value');

        // Only absent rows inherit the global value. Explicit false overrides stay false.
        if ($raw === null && $scope !== '__laravel_null' && config('pennant-manager.inherit_global', false)) {
            $raw = FeatureStorage::query()->where('name', $featureName)
                ->where('scope', '__laravel_null')->value('value');
        }

        if ($raw === null) {
            return false;
        }

        if (! static::isActive($raw)) {
            return false;
        }

        return static::resolve($raw);
    }
}
