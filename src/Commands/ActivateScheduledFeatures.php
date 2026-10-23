<?php

namespace daacreators\PennantManager\Commands;

use Carbon\Carbon;
use daacreators\PennantManager\Services\ScopeRollout;
use daacreators\PennantManager\Support\FeatureStorage;
use daacreators\PennantManager\Support\FeatureValue;
use Illuminate\Console\Command;
use Laravel\Pennant\Feature;

class ActivateScheduledFeatures extends Command
{
    protected $signature = 'pennant:activate-scheduled';

    protected $description = 'Activate any features whose scheduled activation time has passed';

    public function handle(): void
    {
        $activated = 0;
        FeatureStorage::query()->where('scope', '__laravel_null')->orderBy('id')->chunkById(100, function ($features) use (&$activated) {
            foreach ($features as $feature) {
                try {
                    $changed = FeatureStorage::connection()->transaction(function () use ($feature) {
                        $current = FeatureStorage::query()->where('id', $feature->id)->lockForUpdate()->first();
                        if (! $current) {
                            return false;
                        }
                        $data = FeatureValue::decode($current->value);
                        if (empty($data['activate_at']) || ($data['enabled'] ?? false) || now()->lt(Carbon::parse($data['activate_at']))) {
                            return false;
                        }
                        if (! empty($data['percentage']) && ! empty($data['scope_type'])) {
                            ScopeRollout::apply($current->name, $data['scope_type'], (int) $data['percentage']);
                        }
                        $data['enabled'] = true;
                        FeatureStorage::query()->where('id', $current->id)->update(['value' => json_encode($data), 'updated_at' => now()]);

                        return true;
                    });
                    $activated += (int) $changed;
                } catch (\Throwable $exception) {
                    $this->warn("Skipped scheduled feature {$feature->name}: {$exception->getMessage()}");
                    report($exception);
                }
            }
        });
        if ($activated > 0) {
            Feature::flushCache();
        }
        $this->info("Activated {$activated} scheduled feature(s).");
    }
}
