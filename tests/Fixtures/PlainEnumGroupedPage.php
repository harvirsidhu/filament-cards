<?php

namespace Harvirsidhu\FilamentCards\Tests\Fixtures;

use Filament\Pages\Page;
use UnitEnum;

class PlainEnumGroupedPage extends Page
{
    protected string $view = 'filament-panels::pages.dashboard';

    protected static string | UnitEnum | null $navigationGroup = PlainGroup::Operations;

    public static function getUrl(array $parameters = [], bool $isAbsolute = true, ?string $panel = null, $tenant = null, bool $shouldGuessMissingParameters = false): string
    {
        return '/testing/plain-enum';
    }
}
