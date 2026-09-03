<?php

namespace Harvirsidhu\FilamentCards\Tests\Fixtures;

class HubProfilePage extends HubResourcePage
{
    protected static ?int $navigationSort = 1;

    public static function getNavigationLabel(): string
    {
        return 'Hub Profile';
    }
}
