<?php

namespace Harvirsidhu\FilamentCards\Tests\Fixtures;

use Filament\Resources\Resource;

class HubTestResource extends Resource
{
    public static function getNavigationLabel(): string
    {
        return 'Hub Resource';
    }

    /**
     * @return array<string, mixed>
     */
    public static function getPages(): array
    {
        return [
            'profile' => new FakePageRegistration(HubProfilePage::class),
            'billing' => new FakePageRegistration(HubBillingPage::class),
        ];
    }
}
