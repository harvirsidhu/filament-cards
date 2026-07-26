<?php

use Filament\Pages\Page;
use Harvirsidhu\FilamentCards\CardGroup;
use Harvirsidhu\FilamentCards\CardItem;
use Harvirsidhu\FilamentCards\Filament\Pages\CardsPage;
use Illuminate\Support\Collection;

/**
 * Card groups must come out in the same order Filament puts them in the
 * sidebar — by their lowest $navigationSort — otherwise a cluster's front page
 * contradicts its own navigation.
 *
 * Sorts below are deliberately non-contiguous and declared out of order, so a
 * pass only means the sort was honoured, not that discovery order happened to
 * look right.
 */
class OrderingBillingPage extends Page
{
    protected static string | UnitEnum | null $navigationGroup = 'Billing';

    protected static ?int $navigationSort = 39;
}

class OrderingInventoryPage extends Page
{
    protected static string | UnitEnum | null $navigationGroup = 'Inventory';

    protected static ?int $navigationSort = 30;
}

class OrderingProfilePage extends Page
{
    protected static string | UnitEnum | null $navigationGroup = 'Profile';

    protected static ?int $navigationSort = 1;
}

class OrderingCommsFirstPage extends Page
{
    protected static string | UnitEnum | null $navigationGroup = 'Communications';

    protected static ?int $navigationSort = 5;
}

class OrderingCommsSecondPage extends Page
{
    protected static string | UnitEnum | null $navigationGroup = 'Communications';

    protected static ?int $navigationSort = 8;
}

class OrderingUngroupedPage extends Page
{
    protected static ?int $navigationSort = 99;
}

class OrderingCardsPage extends CardsPage
{
    protected static function getCards(): array
    {
        return [];
    }

    /** @return array<CardGroup|CardItem> */
    public static function group(Collection $items): array
    {
        return static::groupItemsByNavigation($items);
    }
}

/** @param  array<int, class-string>  $pages */
function orderingItems(array $pages): Collection
{
    return collect($pages)->map(
        fn (string $page): CardItem => CardItem::make($page)->sort($page::getNavigationSort() ?? 0),
    );
}

/** @param  array<int, CardGroup|CardItem>  $result */
function orderingGroupLabels(array $result): array
{
    return collect($result)
        ->filter(fn ($entry): bool => $entry instanceof CardGroup)
        ->map(fn (CardGroup $group): string => $group->getLabel())
        ->values()
        ->all();
}

it('orders groups by their lowest navigation sort, not by discovery order', function () {
    // Passed in the order the filesystem tends to yield them — the order the
    // page used to render, and the bug this guards.
    $result = OrderingCardsPage::group(orderingItems([
        OrderingInventoryPage::class,
        OrderingBillingPage::class,
        OrderingProfilePage::class,
        OrderingCommsFirstPage::class,
    ]));

    expect(orderingGroupLabels($result))->toBe([
        'Profile',          // 1
        'Communications',   // 5
        'Inventory',        // 30
        'Billing',          // 39
    ]);
});

it('still sorts the cards inside each group', function () {
    $result = OrderingCardsPage::group(orderingItems([
        OrderingCommsSecondPage::class,
        OrderingCommsFirstPage::class,
    ]));

    $comms = collect($result)->first(fn ($entry): bool => $entry instanceof CardGroup);

    expect(collect($comms->getItems())->map(fn (CardItem $item): int => $item->getSort())->all())
        ->toBe([5, 8]);
});

it('puts ungrouped cards before the groups, never between them', function () {
    // A lone card emitted mid-iteration lands under whichever heading precedes
    // it and reads as part of that group.
    $result = OrderingCardsPage::group(orderingItems([
        OrderingProfilePage::class,
        OrderingUngroupedPage::class,
        OrderingBillingPage::class,
    ]));

    expect($result[0])->toBeInstanceOf(CardItem::class)
        ->and(orderingGroupLabels($result))->toBe(['Profile', 'Billing']);
});

it('breaks ties on group name so the grid is reproducible across platforms', function () {
    // sortBy() is not stable in PHP, so equal sorts would otherwise fall back
    // to discovery order and differ between machines.
    $tied = collect([
        CardItem::make(OrderingBillingPage::class)->sort(10),
        CardItem::make(OrderingProfilePage::class)->sort(10),
        CardItem::make(OrderingInventoryPage::class)->sort(10),
    ]);

    expect(orderingGroupLabels(OrderingCardsPage::group($tied)))
        ->toBe(['Billing', 'Inventory', 'Profile']);
});
