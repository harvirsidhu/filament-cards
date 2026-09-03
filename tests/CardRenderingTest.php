<?php

use Filament\Support\Enums\Alignment;
use Filament\Support\Enums\IconPosition;
use Filament\Support\Enums\IconSize;
use Harvirsidhu\FilamentCards\CardGroup;
use Harvirsidhu\FilamentCards\CardItem;
use Harvirsidhu\FilamentCards\Tests\Fixtures\ForbiddenTestPage;
use Illuminate\Support\Facades\Blade;

/**
 * These render the view rather than asserting on the PHP objects, because
 * every layout bug this suite exists to catch was invisible from PHP: the
 * responsive column config, the column spans and the hover colours all
 * resolved fine in the object graph and then produced Tailwind classes that
 * were never compiled. Only the emitted HTML shows that.
 */
function renderGroup(CardGroup $group, array $data = []): string
{
    return Blade::render(
        '<x-harvirsidhu-filament-cards::group :group="$group" :page-columns="$pageColumns" :page-key="$pageKey" :alignment="$alignment" :icon-position="$iconPosition" :icon-size="$iconSize" :is-icon-inlined="$isIconInlined" :is-searchable="$isSearchable" :should-persist-collapsed="$shouldPersistCollapsed" />',
        [
            'group' => $group,
            'pageColumns' => $data['pageColumns'] ?? 3,
            'pageKey' => $data['pageKey'] ?? 'test-page',
            'alignment' => $data['alignment'] ?? Alignment::Center,
            'iconPosition' => $data['iconPosition'] ?? IconPosition::Before,
            'iconSize' => $data['iconSize'] ?? IconSize::Medium,
            'isIconInlined' => $data['isIconInlined'] ?? false,
            'isSearchable' => $data['isSearchable'] ?? false,
            'shouldPersistCollapsed' => $data['shouldPersistCollapsed'] ?? false,
        ],
    );
}

it('renders responsive columns as Filament grid custom properties', function () {
    $html = renderGroup(
        CardGroup::make('Responsive')
            ->columns(['md' => 2, 'xl' => 4])
            ->schema([CardItem::make('https://example.com')->label('One')]),
    );

    // The old view interpolated "{$prefix}grid-cols-{$count}", which Tailwind
    // never saw and therefore never generated.
    expect($html)
        ->toContain('fi-grid')
        ->toContain('md:fi-grid-cols')
        ->toContain('xl:fi-grid-cols')
        ->toContain('--cols-md: repeat(2, minmax(0, 1fr))')
        ->toContain('--cols-xl: repeat(4, minmax(0, 1fr))')
        // A config that starts at md still needs a base column count.
        ->toContain('--cols-default: repeat(1, minmax(0, 1fr))')
        ->not->toContain('md:grid-cols-2');
});

it('keeps the progressive breakpoints for a scalar column count', function () {
    $html = renderGroup(
        CardGroup::make('Scalar')
            ->columns(3)
            ->schema([CardItem::make('https://example.com')->label('One')]),
    );

    expect($html)
        ->toContain('--cols-default: repeat(1, minmax(0, 1fr))')
        ->toContain('--cols-md: repeat(2, minmax(0, 1fr))')
        ->toContain('--cols-lg: repeat(3, minmax(0, 1fr))');
});

it('reaches the 2xl breakpoint for column counts above four', function () {
    $html = renderGroup(
        CardGroup::make('Wide')
            ->columns(6)
            ->schema([CardItem::make('https://example.com')->label('One')]),
    );

    expect($html)->toContain('--cols-2xl: repeat(6, minmax(0, 1fr))');
});

it('renders column spans at every breakpoint, not just lg', function () {
    $html = renderGroup(
        CardGroup::make('Spans')->schema([
            CardItem::make('https://example.com')
                ->label('Wide')
                ->columnSpan(['default' => 1, 'md' => 2, 'lg' => 3]),
        ]),
    );

    expect($html)
        ->toContain('fi-grid-col')
        ->toContain('--col-span-md: span 2 / span 2')
        // The md span used to be dropped entirely: the view only read
        // 'default' and 'lg' out of the span array.
        ->toContain('--col-span-lg: span 3 / span 3');
});

it('renders columnSpanFull as a real full-width span', function () {
    $html = renderGroup(
        CardGroup::make('Full')->schema([
            CardItem::make('https://example.com')->label('Full')->columnSpanFull(),
        ]),
    );

    expect($html)->toContain('--col-span-default: 1 / -1');
});

it('applies the hover colour to both themes without tinting dark mode at rest', function () {
    $html = renderGroup(
        CardGroup::make('Coloured')->schema([
            CardItem::make('https://example.com')
                ->label('Success')
                ->icon('heroicon-o-check')
                ->color('success'),
        ]),
    );

    expect($html)
        ->toContain('fi-color-success')
        ->toContain('group-hover:text-color-500')
        ->toContain('dark:group-hover:text-color-400')
        // The bug: "group-hover:{$class}" expanded to
        // "group-hover:text-success-500 dark:text-success-400", leaving the
        // dark variant applied at rest rather than on hover.
        ->not->toContain('group-hover:text-success-500 dark:text-success-400');
});

it('supports a colour the package has never heard of', function () {
    $html = renderGroup(
        CardGroup::make('Custom')->schema([
            CardItem::make('https://example.com')->label('Brand')->color('brand'),
        ]),
    );

    // The old hardcoded map silently dropped anything outside its six names.
    expect($html)->toContain('fi-color-brand');
});

it('renders a card with no destination as plain content rather than a dead link', function () {
    $html = renderGroup(
        CardGroup::make('Dead')->schema([
            CardItem::make('https://example.com')->label('Nowhere')->url(null),
        ]),
    );

    expect($html)->not->toContain('href="#"');
});

it('marks a disabled card as disabled for assistive technology', function () {
    $html = renderGroup(
        CardGroup::make('Disabled')->schema([
            CardItem::make('https://example.com')->label('Off')->disabled(),
        ]),
    );

    expect($html)
        ->toContain('aria-disabled="true"')
        ->not->toContain('<a');
});

it('gives a collapsible group a real button carrying the expanded state', function () {
    $html = renderGroup(
        CardGroup::make('Collapsible')
            ->collapsible()
            ->schema([CardItem::make('https://example.com')->label('One')]),
    );

    expect($html)
        ->toContain('<button')
        ->toContain('aria-expanded')
        ->toContain('aria-controls');
});

it('persists collapse state when asked', function () {
    $html = renderGroup(
        CardGroup::make('Sticky')
            ->collapsed()
            ->persistCollapsed()
            ->schema([CardItem::make('https://example.com')->label('One')]),
    );

    expect($html)->toContain('$persist');
});

it('scopes persisted collapse state to the page as well as the group', function () {
    $html = renderGroup(
        CardGroup::make('Billing')
            ->collapsible()
            ->persistCollapsed()
            ->schema([CardItem::make('https://example.com')->label('One')]),
        ['pageKey' => 'my-page'],
    );

    expect($html)->toContain('filament-cards-my-page-billing-isCollapsed');
});

it('indexes both raw and accent-folded text for search', function () {
    $html = renderGroup(
        CardGroup::make('Search')->schema([
            CardItem::make('https://example.com')->label('Präferenzen'),
        ]),
        ['isSearchable' => true],
    );

    expect($html)
        ->toContain('präferenzen')
        ->toContain('praferenzen');
});

it('includes search keywords in the indexed text', function () {
    $html = renderGroup(
        CardGroup::make('Search')->schema([
            CardItem::make('https://example.com')
                ->label('Billing')
                ->searchKeywords(['invoice', 'payment']),
        ]),
        ['isSearchable' => true],
    );

    expect($html)
        ->toContain('invoice')
        ->toContain('payment');
});

it('hides a card whose page the user cannot access', function () {
    $group = CardGroup::make('Guarded')->schema([
        CardItem::make(ForbiddenTestPage::class),
    ]);

    expect($group->getItems())->toBeEmpty();

    expect(renderGroup($group))->not->toContain('Forbidden Page');
});

it('still shows an inaccessible page when the access check is opted out of', function () {
    $group = CardGroup::make('Guarded')->schema([
        CardItem::make(ForbiddenTestPage::class)->checkAccess(false),
    ]);

    expect($group->getItems())->toHaveCount(1);
});

it('announces that a card opens in a new tab', function () {
    $html = renderGroup(
        CardGroup::make('External')->schema([
            CardItem::make('https://example.com')->label('Docs')->openUrlInNewTab(),
        ]),
    );

    expect($html)
        ->toContain('target="_blank"')
        ->toContain('fi-sr-only');
});

it('renders a contained group inside a Filament section', function () {
    $html = renderGroup(
        CardGroup::make('Contained')
            ->contained()
            ->schema([CardItem::make('https://example.com')->label('One')]),
    );

    expect($html)->toContain('fi-section');
});

it('renders nothing for a group with no visible items', function () {
    $html = renderGroup(
        CardGroup::make('Empty')->schema([
            CardItem::make('https://example.com')->label('Hidden')->hidden(),
        ]),
    );

    expect(trim($html))->toBe('');
});

it('indexes the group label against each of its cards for search', function () {
    $html = renderGroup(
        CardGroup::make('Billing')->schema([
            CardItem::make('https://example.com')->label('Invoices'),
        ]),
        ['isSearchable' => true],
    );

    // Searching "billing" should surface a card that never says "Billing".
    expect($html)->toMatch('/data-search-text="[^"]*billing[^"]*"/');
});

/**
 * A card icon is the card's main visual anchor. Filament's `fi-size-*` scale
 * is built for inline glyphs and puts Medium at 20px, half the 40px this
 * package has always used, so the card sizes its icon on its own scale.
 */
it('renders card icons at their own scale, not Filament inline-glyph sizes', function () {
    $html = renderGroup(
        CardGroup::make('Icons')->schema([
            CardItem::make('https://example.com')->label('One')->icon('heroicon-o-check'),
        ]),
    );

    expect($html)->toContain('size-10');
});

it('scales the card icon with the page icon size setting', function () {
    $cases = [
        [\Filament\Support\Enums\IconSize::Small, 'size-6'],
        [\Filament\Support\Enums\IconSize::Medium, 'size-10'],
        [\Filament\Support\Enums\IconSize::Large, 'size-12'],
    ];

    foreach ($cases as [$size, $expected]) {
        $html = renderGroup(
            CardGroup::make('Icons')->schema([
                CardItem::make('https://example.com')->label('One')->icon('heroicon-o-check'),
            ]),
            ['iconSize' => $size],
        );

        expect($html)->toContain($expected);
    }
});
