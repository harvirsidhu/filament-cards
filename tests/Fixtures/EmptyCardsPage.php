<?php

namespace Harvirsidhu\FilamentCards\Tests\Fixtures;

use Harvirsidhu\FilamentCards\Filament\Pages\CardsPage;

class EmptyCardsPage extends CardsPage
{
    protected static function getCards(): array
    {
        return [];
    }
}
