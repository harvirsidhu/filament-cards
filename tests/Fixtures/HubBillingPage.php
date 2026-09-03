<?php

namespace Harvirsidhu\FilamentCards\Tests\Fixtures;

class HubBillingPage extends HubResourcePage
{
    protected static ?int $navigationSort = 2;

    public static function getNavigationLabel(): string
    {
        return 'Hub Billing';
    }
}
