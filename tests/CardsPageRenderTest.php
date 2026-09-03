<?php

use Harvirsidhu\FilamentCards\CardItem;
use Harvirsidhu\FilamentCards\Tests\Fixtures\EmptyCardsPage;
use Harvirsidhu\FilamentCards\Tests\Fixtures\ExampleCardsPage;

use function Pest\Livewire\livewire;

afterEach(function () {
    ExampleCardsPage::flushAppendedCards();
});

it('renders the whole page', function () {
    livewire(ExampleCardsPage::class)
        ->assertOk()
        ->assertSee('Loose Card')
        ->assertSee('Billing')
        ->assertSee('Invoices');
});

it('renders ungrouped cards above the groups, as the sidebar orders them', function () {
    $html = livewire(ExampleCardsPage::class)->html();

    expect(strpos($html, 'Loose Card'))->toBeLessThan(strpos($html, 'Invoices'));
});

it('shows the search field and its screen-reader label when searchable', function () {
    livewire(ExampleCardsPage::class)
        ->assertSee('Search...')
        ->assertSeeHtml('aria-live="polite"');
});

it('shows an empty state when the page has no cards', function () {
    livewire(EmptyCardsPage::class)
        ->assertOk()
        ->assertSee(__('filament-cards::cards.empty.heading'));
});

it('renders cards appended at runtime', function () {
    ExampleCardsPage::addCards([
        CardItem::make('https://example.com/late')->label('Late Addition'),
    ]);

    livewire(ExampleCardsPage::class)->assertSee('Late Addition');
});

it('does not leak runtime cards into another page', function () {
    ExampleCardsPage::addCards([
        CardItem::make('https://example.com/late')->label('Late Addition'),
    ]);

    livewire(EmptyCardsPage::class)->assertDontSee('Late Addition');
});
