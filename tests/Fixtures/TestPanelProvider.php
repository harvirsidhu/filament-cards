<?php

namespace Harvirsidhu\FilamentCards\Tests\Fixtures;

use Filament\Panel;
use Filament\PanelProvider;
use Harvirsidhu\FilamentCards\FilamentCardsPlugin;

class TestPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('testing')
            ->path('testing')
            ->plugin(FilamentCardsPlugin::make());
    }
}
