<?php

namespace Harvirsidhu\FilamentCards\Tests\Fixtures;

use Harvirsidhu\FilamentCards\CardGroup;
use Harvirsidhu\FilamentCards\CardItem;
use Harvirsidhu\FilamentCards\Filament\Pages\CardsPage;

class ExampleCardsPage extends CardsPage
{
    protected static bool $searchable = true;

    protected static function getCards(): array
    {
        return [
            CardItem::make('https://example.com/loose')
                ->label('Loose Card')
                ->description('Not in any group'),

            CardGroup::make('Billing')
                ->collapsible()
                ->schema([
                    CardItem::make('https://example.com/invoices')
                        ->label('Invoices')
                        ->icon('heroicon-o-document-text')
                        ->color('success')
                        ->badge('3'),
                ]),
        ];
    }
}
