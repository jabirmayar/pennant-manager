<?php

namespace daacreators\PennantManager\Tests;

use daacreators\PennantManager\PennantManagerPlugin;
use Filament\Panel;
use Filament\PanelProvider;

class TestPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel->id('test')->default()->path('test')->plugin(PennantManagerPlugin::make());
    }
}
