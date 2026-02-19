<?php

use Harvirsidhu\FilamentCards\CardItem;
use Harvirsidhu\FilamentCards\Filament\Pages\CardsPage;
use Illuminate\Support\HtmlString;

class BadgeTestResource
{
    public static function getNavigationBadge(): string
    {
        return '24';
    }

    public static function getNavigationBadgeColor(): string
    {
        return 'success';
    }
}

class BadgeTestPageWithBadge
{
    public static function getNavigationBadge(): string
    {
        return 'New';
    }

    public static function getNavigationBadgeColor(): string
    {
        return 'primary';
    }
}

class BadgeTestPageWithResourceFallback
{
    public static function getResource(): string
    {
        return BadgeTestResource::class;
    }
}

class BadgeTestCardsPage extends CardsPage
{
    protected static function getCards(): array
    {
        return [];
    }

    public static function resolveNavigationBadge(string $class): string | HtmlString | null
    {
        return static::resolveDiscoveredNavigationBadge($class);
    }

    public static function resolveNavigationBadgeColor(string $class): string | array | null
    {
        return static::resolveDiscoveredNavigationBadgeColor($class);
    }
}

it('supports fluent badge methods', function () {
    $item = CardItem::make('/settings')
        ->badge('Beta')
        ->badgeColor('warning');

    expect($item->getBadge())->toBe('Beta')
        ->and($item->getBadgeColor())->toBe('warning');
});

it('resolves badge data from page navigation methods', function () {
    $item = new CardItem(page: BadgeTestPageWithBadge::class);

    expect($item->getBadge())->toBe('New')
        ->and($item->getBadgeColor())->toBe('primary');
});

it('prefers explicitly configured badge values over discovered values', function () {
    $item = new CardItem(page: BadgeTestPageWithBadge::class);

    $item
        ->badge('Override')
        ->badgeColor('danger');

    expect($item->getBadge())->toBe('Override')
        ->and($item->getBadgeColor())->toBe('danger');
});

it('falls back to resource badge values when page does not define badge methods', function () {
    $item = new CardItem(page: BadgeTestPageWithResourceFallback::class);

    expect($item->getBadge())->toBe('24')
        ->and($item->getBadgeColor())->toBe('success');
});

it('returns null badge values when navigation methods are not available', function () {
    $item = new CardItem(page: stdClass::class);

    expect($item->getBadge())->toBeNull()
        ->and($item->getBadgeColor())->toBeNull();
});

it('exposes safe CardsPage badge helpers when methods are missing', function () {
    expect(BadgeTestCardsPage::resolveNavigationBadge(stdClass::class))->toBeNull()
        ->and(BadgeTestCardsPage::resolveNavigationBadgeColor(stdClass::class))->toBeNull();
});
