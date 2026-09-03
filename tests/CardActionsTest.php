<?php

use Harvirsidhu\FilamentCards\Tests\Fixtures\ActionCardsPage;

use function Pest\Livewire\livewire;

beforeEach(fn () => ActionCardsPage::$synced = false);

it('renders a card action', function () {
    livewire(ActionCardsPage::class)
        ->assertOk()
        ->assertSee('Sync now');
});

it('runs a card action', function () {
    livewire(ActionCardsPage::class)
        ->callAction('syncCache');

    expect(ActionCardsPage::$synced)->toBeTrue();
});

/**
 * A <button> inside an <a> is invalid HTML and unreachable by keyboard, so a
 * card carrying actions must not be wrapped in the anchor; it gets a
 * stretched overlay link instead.
 */
it('does not nest the action button inside the card anchor', function () {
    $html = livewire(ActionCardsPage::class)->html();

    $card = substr($html, strpos($html, 'fi-cards-card'));
    $card = substr($card, 0, strpos($card, '</div>'));

    expect($card)->not->toStartWith('<a');
    expect($html)->toContain('absolute inset-0 z-0');
});
