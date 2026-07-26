# Changelog

All notable changes to `filament-cards` will be documented in this file.

## 1.0.9 - 2026-07-27

### Fixed

- Card groups now render in the same order Filament uses for the sidebar — by each group's lowest `$navigationSort`. Previously group order followed component discovery order (effectively filesystem order), so a cluster's front page could contradict its own navigation.
- Ungrouped cards now render above all groups instead of inline between them, where a lone card appeared to belong to the preceding group heading.

## 1.0.8 - 2026-05-04

### Fixed

- Card links are now rendered with Filament's `generate_href_html()` helper, so SPA navigation (`wire:navigate`) works on cards instead of forcing a full page load. Thanks [@webard](https://github.com/webard) ([#1](https://github.com/harvirsidhu/filament-cards/pull/1)).

### Changed

- Rewrote the README.

## 1.0.7 - 2026-05-04

### Added

- Searchable cards. `CardsPage` can now render a search input that filters cards client-side as you type.

## 1.0.6 - 2026-02-19

### Changed

- Replaced the auto-detecting `external()` API on `CardItem` with the existing `openUrlInNewTab()`. The external-link icon is now driven by that flag rather than by comparing the card's URL host against `app.url`, which was guesswork that misfired behind proxies and on multi-domain setups.

### Removed

- `CardItem::external()` and `CardItem::isExternal()`.

## 1.0.5 - 2026-02-19

### Fixed

- External-link detection no longer treats cards as external when it cannot tell. Pages and resources without an explicit `url()` override are always internal, and a blank or unparseable `app.url` now yields `false` instead of `true` — previously an unset `app.url` marked every card external.

## 1.0.4 - 2026-02-19

### Added

- External-link icon on card items, with `CardItem::external()` to force the state and automatic detection from the card's URL.

## 1.0.3 - 2026-02-19

### Added

- Badges on card items. Values are read from the page's or resource's navigation badge methods when not set explicitly, with `badge()`, `badgeColor()`, and `badgeTooltip()` to override.
- Screenshot in the README.

## 1.0.2 - 2026-02-19

### Changed

- `columns()` on `CardsPage` and `CardGroup` now accepts Filament's responsive array and string forms (e.g. `['sm' => 1, 'lg' => 3]`), not just an integer.
- Default item alignment is now `Alignment::Center` instead of `Alignment::Start`.

## 1.0.1 - 2026-02-19

### Added

- Card descriptions, discovered from a page's or resource's `getNavigationDescription()` method or `$navigationDescription` property.
- `$excludedClusterComponents` and `$excludedResourcePages` on `CardsPage` to omit specific components from the grid.
- `alignment()` on `CardItem` for per-card alignment, and `$iconPosition` on `CardsPage`.

### Changed

- Use Filament's own `Filament\Support\Enums\Alignment` instead of shipping a duplicate enum.
- Renamed the view namespace to `harvirsidhu-filament-cards` to avoid colliding with other packages.

### Removed

- `Harvirsidhu\FilamentCards\Enums\Alignment`. Import `Filament\Support\Enums\Alignment` instead.

## 1.0.0 - 2026-02-18

- initial release
