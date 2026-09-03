<?php

namespace Harvirsidhu\FilamentCards\Tests\Fixtures;

use Filament\Resources\Pages\Page as ResourcePage;

abstract class HubResourcePage extends ResourcePage
{
    protected string $view = 'filament-panels::pages.dashboard';

    public static function getResource(): string
    {
        return HubTestResource::class;
    }

    public static function getUrl(array $parameters = [], bool $isAbsolute = true, ?string $panel = null, $tenant = null, bool $shouldGuessMissingParameters = false): string
    {
        return '/testing/' . static::class;
    }
}
