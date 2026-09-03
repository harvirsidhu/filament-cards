<?php

use Filament\Support\Enums\Alignment;
use Filament\Support\Enums\IconSize;
use Harvirsidhu\FilamentCards\CardGroup;
use Harvirsidhu\FilamentCards\CardItem;
use Harvirsidhu\FilamentCards\Filament\Pages\CardsPage;
use Harvirsidhu\FilamentCards\FilamentCardsPlugin;
use Harvirsidhu\FilamentCards\Support\GridColumns;

class ConfigDefaultsPage extends CardsPage {}

class ConfigOverridingPage extends CardsPage
{
    protected static int | string | array $columns = 5;

    protected static Alignment $itemsAlignment = Alignment::Start;

    protected static IconSize $iconSize = IconSize::Large;

    protected static bool $searchable = true;

    protected static ?string $searchPlaceholder = 'Find a tool...';
}

class AppendTargetPageA extends CardsPage {}

class AppendTargetPageB extends CardsPage {}

/**
 * The plugin instance lives on the panel and therefore survives between
 * tests in the same process. Without this reset, a test that sets a panel
 * default would decide the outcome of any later test that asserts on the
 * package defaults — and the suite runs in a random order.
 */
beforeEach(function () {
    FilamentCardsPlugin::get()
        ->columns(null)
        ->itemsAlignment(null)
        ->iconSize(null)
        ->iconPosition(null)
        ->iconInlined(null)
        ->searchable(null)
        ->searchPlaceholder(null)
        ->persistCollapsed(null);
});

afterEach(function () {
    AppendTargetPageA::flushAppendedCards();
    AppendTargetPageB::flushAppendedCards();
});

/**
 * The regression this guards: `$appendedCards` was a plain static on the
 * abstract base, and PHP shares such a property with every subclass that does
 * not redeclare it — so a card added to one hub page appeared on all of them.
 */
it('keeps appended cards on the page they were added to', function () {
    AppendTargetPageA::addCards([
        CardItem::make('https://example.com/a')->label('Only A'),
    ]);

    expect(AppendTargetPageA::getAppendedCards())->toHaveCount(1);
    expect(AppendTargetPageB::getAppendedCards())->toBeEmpty();
});

it('accumulates several appends on the same page', function () {
    AppendTargetPageA::addCards([CardItem::make('https://example.com/1')->label('One')]);
    AppendTargetPageA::addCards([CardItem::make('https://example.com/2')->label('Two')]);

    expect(AppendTargetPageA::getAppendedCards())->toHaveCount(2);
});

it('uses the package defaults when neither the page nor the plugin sets anything', function () {
    expect(ConfigDefaultsPage::getCardsColumns())->toBe(3)
        ->and(ConfigDefaultsPage::getCardsItemsAlignment())->toBe(Alignment::Center)
        ->and(ConfigDefaultsPage::isCardsSearchable())->toBeFalse();
});

it('lets a page override every setting', function () {
    expect(ConfigOverridingPage::getCardsColumns())->toBe(5)
        ->and(ConfigOverridingPage::getCardsItemsAlignment())->toBe(Alignment::Start)
        ->and(ConfigOverridingPage::getCardsIconSize())->toBe(IconSize::Large)
        ->and(ConfigOverridingPage::isCardsSearchable())->toBeTrue()
        ->and(ConfigOverridingPage::getCardsSearchPlaceholder())->toBe('Find a tool...');
});

it('falls back to the plugin defaults for pages that set nothing', function () {
    FilamentCardsPlugin::get()
        ->columns(4)
        ->itemsAlignment(Alignment::Start)
        ->searchable();

    expect(ConfigDefaultsPage::getCardsColumns())->toBe(4)
        ->and(ConfigDefaultsPage::getCardsItemsAlignment())->toBe(Alignment::Start)
        ->and(ConfigDefaultsPage::isCardsSearchable())->toBeTrue();
});

it('still prefers a page setting over the plugin default', function () {
    FilamentCardsPlugin::get()->columns(4)->itemsAlignment(Alignment::End);

    expect(ConfigOverridingPage::getCardsColumns())->toBe(5)
        ->and(ConfigOverridingPage::getCardsItemsAlignment())->toBe(Alignment::Start);
});

it('translates the default search placeholder through the package namespace', function () {
    expect(ConfigDefaultsPage::getCardsSearchPlaceholder())
        ->toBe(__('filament-cards::cards.search.placeholder'))
        ->toBe('Search...');
});

it('normalises a scalar column count into progressive breakpoints', function () {
    expect(GridColumns::normalize(4))->toBe([
        'default' => 1,
        'md' => 2,
        'lg' => 3,
        'xl' => 4,
    ]);
});

it('gives a responsive column config a base value', function () {
    expect(GridColumns::normalize(['md' => 2, 'xl' => 4]))->toBe([
        'md' => 2,
        'xl' => 4,
        'default' => 1,
    ]);
});

it('clamps out-of-range column counts', function () {
    expect(GridColumns::normalize(['default' => 99]))->toBe(['default' => 12])
        ->and(GridColumns::normalize(['default' => 0]))->toBe(['default' => 1]);
});

it('accepts numeric strings for column counts', function () {
    expect(GridColumns::normalize('2'))->toBe(['default' => 1, 'md' => 2]);
});

it('derives a stable group id from the label', function () {
    expect(CardGroup::make('People & Access')->getId())->toBe('people-access')
        ->and(CardGroup::make(null)->getId())->toBe('ungrouped')
        ->and(CardGroup::make('Anything')->id('custom')->getId())->toBe('custom');
});
