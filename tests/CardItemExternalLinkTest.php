<?php

use Harvirsidhu\FilamentCards\CardItem;

it('auto-detects external links by host', function () {
    config()->set('app.url', 'https://app.example.com');

    $item = CardItem::make('https://docs.example.com');

    expect($item->isExternal())->toBeTrue()
        ->and($item->shouldOpenUrlInNewTab())->toBeTrue();
});

it('does not mark same-host absolute links as external', function () {
    config()->set('app.url', 'https://app.example.com');

    $item = CardItem::make('https://app.example.com/settings');

    expect($item->isExternal())->toBeFalse()
        ->and($item->shouldOpenUrlInNewTab())->toBeFalse();
});

it('does not mark relative links as external', function () {
    $item = CardItem::make('/settings/profile');

    expect($item->isExternal())->toBeFalse()
        ->and($item->shouldOpenUrlInNewTab())->toBeFalse();
});

it('allows forcing external behavior', function () {
    $item = CardItem::make('/docs')->external();

    expect($item->isExternal())->toBeTrue()
        ->and($item->shouldOpenUrlInNewTab())->toBeTrue();
});

it('allows disabling external behavior explicitly', function () {
    $item = CardItem::make('https://docs.example.com')->external(false);

    expect($item->isExternal())->toBeFalse()
        ->and($item->shouldOpenUrlInNewTab())->toBeFalse();
});

it('still supports manually opening a link in a new tab', function () {
    $item = CardItem::make('/settings')->openUrlInNewTab();

    expect($item->isExternal())->toBeFalse()
        ->and($item->shouldOpenUrlInNewTab())->toBeTrue();
});
