<?php

use Harvirsidhu\FilamentCards\CardItem;
use Harvirsidhu\FilamentCards\Filament\Pages\CardsPage;
use Harvirsidhu\FilamentCards\Tests\Fixtures\HubBillingPage;
use Harvirsidhu\FilamentCards\Tests\Fixtures\HubTestResource;

/**
 * `discoverResourceCards()` was gated behind
 * `is_a(static::class, Filament\Resources\Pages\Page::class, true)`, which no
 * subclass of CardsPage can ever satisfy: CardsPage extends
 * Filament\Pages\Page, and the resource page class is a sibling of that, not
 * an ancestor. The guard was always false, so the documented resource hub
 * silently rendered an empty page.
 */
class ResourceHubPage extends CardsPage
{
    protected static string $resource = HubTestResource::class;

    public static function cards(): array
    {
        return static::discoverResourceCards();
    }
}

class ResourceHubExcludingPage extends CardsPage
{
    protected static string $resource = HubTestResource::class;

    protected static array $excludedResourcePages = [
        HubBillingPage::class,
    ];

    public static function cards(): array
    {
        return static::discoverResourceCards();
    }
}

class ResourceHubWithoutResourcePage extends CardsPage
{
    public static function cards(): array
    {
        return static::discoverResourceCards();
    }
}

it('discovers the pages registered on the resource named by $resource', function () {
    $cards = ResourceHubPage::cards();

    expect($cards)->not->toBeEmpty();

    $labels = array_map(
        fn (CardItem $item): string => (string) $item->getLabel(),
        $cards,
    );

    expect($labels)->toContain('Hub Profile')
        ->and($labels)->toContain('Hub Billing');
});

it('honours $excludedResourcePages', function () {
    $labels = array_map(
        fn (CardItem $item): string => (string) $item->getLabel(),
        ResourceHubExcludingPage::cards(),
    );

    expect($labels)->toContain('Hub Profile')
        ->and($labels)->not->toContain('Hub Billing');
});

it('returns nothing when the page names no resource', function () {
    expect(ResourceHubWithoutResourcePage::cards())->toBeEmpty();
});

it('orders discovered resource pages by navigation sort', function () {
    $labels = array_map(
        fn (CardItem $item): string => (string) $item->getLabel(),
        ResourceHubPage::cards(),
    );

    expect($labels)->toBe(['Hub Profile', 'Hub Billing']);
});
