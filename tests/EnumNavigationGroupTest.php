<?php

use Harvirsidhu\FilamentCards\CardGroup;
use Harvirsidhu\FilamentCards\CardItem;
use Harvirsidhu\FilamentCards\Filament\Pages\CardsPage;
use Harvirsidhu\FilamentCards\Tests\Fixtures\EnumGroupedPage;
use Harvirsidhu\FilamentCards\Tests\Fixtures\PlainEnumGroupedPage;

class EnumGroupingPage extends CardsPage
{
    public static function group(array $pages): array
    {
        return static::groupItemsByNavigation(
            collect($pages)->map(fn (string $page): CardItem => CardItem::make($page)),
        );
    }
}

/**
 * Filament 4 lets a navigation group be an enum. The sidebar shows the
 * enum's label when it implements HasLabel and the case name otherwise —
 * never the backing value, which is what an earlier version of this
 * package used.
 */
it('labels an enum group by its label, not its backing value', function () {
    [$group] = EnumGroupingPage::group([EnumGroupedPage::class]);

    expect($group)->toBeInstanceOf(CardGroup::class)
        ->and((string) $group->getLabel())->toBe('Finance & Billing')
        ->and((string) $group->getLabel())->not->toBe('fin');
});

it('falls back to the case name for a unit enum', function () {
    [$group] = EnumGroupingPage::group([PlainEnumGroupedPage::class]);

    expect((string) $group->getLabel())->toBe('Operations');
});
