<?php

namespace Harvirsidhu\FilamentCards\Tests\Fixtures;

use Filament\Pages\Page;

class PlainTestPage extends Page
{
    protected string $view = 'filament-panels::pages.dashboard';

    public static function getNavigationLabel(): string
    {
        return 'Plain Page';
    }

    public static function getUrl(array $parameters = [], bool $isAbsolute = true, ?string $panel = null, $tenant = null, bool $shouldGuessMissingParameters = false): string
    {
        return '/testing/plain';
    }
}
