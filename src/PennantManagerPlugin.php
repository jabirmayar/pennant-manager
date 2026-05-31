<?php

namespace daacreators\PennantManager;

use daacreators\PennantManager\Resources\FeatureResource;
use daacreators\PennantManager\Widgets\FeatureAdoptionWidget;
use Filament\Contracts\Plugin;
use Filament\Panel;

class PennantManagerPlugin implements Plugin
{
    public function getId(): string
    {
        return 'pennant-manager';
    }

    public function register(Panel $panel): void
    {
        $panel
            ->resources([
                FeatureResource::class,
            ])
            ->widgets([
                FeatureAdoptionWidget::class,
            ]);
    }

    public function boot(Panel $panel): void
    {
    }

    public static function make(): static
    {
        return app(static::class);
    }
}