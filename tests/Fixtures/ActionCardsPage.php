<?php

namespace Harvirsidhu\FilamentCards\Tests\Fixtures;

use Filament\Actions\Action;
use Harvirsidhu\FilamentCards\CardGroup;
use Harvirsidhu\FilamentCards\CardItem;
use Harvirsidhu\FilamentCards\Filament\Pages\CardsPage;

class ActionCardsPage extends CardsPage
{
    public static bool $synced = false;

    protected static function getCards(): array
    {
        return [
            CardGroup::make('Ops')->schema([
                CardItem::make('https://example.com/cache')
                    ->label('Cache')
                    ->actions([
                        Action::make('syncCache')
                            ->label('Sync now')
                            ->action(fn () => static::$synced = true),
                    ]),
            ]),
        ];
    }
}
