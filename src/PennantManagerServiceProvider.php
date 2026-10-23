<?php

namespace daacreators\PennantManager;

use daacreators\PennantManager\Commands\ActivateScheduledFeatures;
use daacreators\PennantManager\Commands\PennantManagerCommand;
use daacreators\PennantManager\Testing\TestsPennantManager;
use Filament\Support\Assets\Asset;
use Filament\Support\Facades\FilamentAsset;
use Filament\Support\Facades\FilamentIcon;
use Illuminate\Filesystem\Filesystem;
use Livewire\Features\SupportTesting\Testable;
use Spatie\LaravelPackageTools\Commands\InstallCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class PennantManagerServiceProvider extends PackageServiceProvider
{
    public static string $name = 'pennant-manager';

    public static string $viewNamespace = 'pennant-manager';

    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package->name(static::$name)
            ->hasConfigFile()
            ->hasViews(static::$viewNamespace)
            ->hasCommands($this->getCommands())
            ->hasInstallCommand(function (InstallCommand $command) {
                $command
                    ->publishConfigFile()
                    ->publishMigrations()
                    ->askToRunMigrations()
                    ->askToStarRepoOnGitHub('jabirmayar/pennant-manager');
            });

        $configFileName = $package->shortName();

        if (file_exists($package->basePath('/../resources/lang'))) {
            $package->hasTranslations();
        }
    }

    public function packageRegistered(): void {}

    public function packageBooted(): void
    {
        // Load views
        $this->loadViewsFrom(
            __DIR__ . '/../resources/views',
            static::$viewNamespace
        );

        // Asset Registration
        FilamentAsset::register(
            $this->getAssets(),
            $this->getAssetPackageName()
        );

        FilamentAsset::registerScriptData(
            $this->getScriptData(),
            $this->getAssetPackageName()
        );

        // Icon Registration
        FilamentIcon::register($this->getIcons());

        // Handle Stubs
        if (app()->runningInConsole() && is_dir(__DIR__ . '/../stubs/')) {
            foreach (app(Filesystem::class)->files(__DIR__ . '/../stubs/') as $file) {
                $this->publishes([
                    $file->getRealPath() => base_path("stubs/pennant-manager/{$file->getFilename()}"),
                ], 'pennant-manager-stubs');
            }
        }

        // Testing
        Testable::mixin(new TestsPennantManager);
    }

    protected function getAssetPackageName(): ?string
    {
        return 'daacreators/pennant-manager';
    }

    /**
     * @return array<Asset>
     */
    protected function getAssets(): array
    {
        return [
            // AlpineComponent::make('pennant-manager', __DIR__ . '/../resources/dist/components/pennant-manager.js'),
            // Css::make('pennant-manager-styles', __DIR__ . '/../resources/dist/pennant-manager.css'),
            // Js::make('pennant-manager-scripts', __DIR__ . '/../resources/dist/pennant-manager.js'),
        ];
    }

    /**
     * @return array<class-string>
     */
    protected function getCommands(): array
    {
        return [
            PennantManagerCommand::class,
            ActivateScheduledFeatures::class,
        ];
    }

    /**
     * @return array<string>
     */
    protected function getIcons(): array
    {
        return [];
    }

    /**
     * @return array<string>
     */
    protected function getRoutes(): array
    {
        return [];
    }

    /**
     * @return array<string, mixed>
     */
    protected function getScriptData(): array
    {
        return [];
    }

    /**
     * @return array<string>
     */
    protected function getMigrations(): array
    {
        return [];
    }
}
