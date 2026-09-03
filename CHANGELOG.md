# Changelog

All notable changes to `filament-cards` will be documented in this file.

## 1.1.0 - 2026-09-04

### Upgrading

Nothing to change in your code, but three fixes alter what you see. Rebuild your CSS (`npm run build`) after updating, then check:

- **Layouts may shift.** `columns(['md' => 2, 'xl' => 4])` and `columnSpan()` silently rendered as a single column before and now work, so pages using them become genuinely responsive.
- **Cards may disappear.** `canAccess()` is now enforced on every card, not only auto-discovered ones. Add `->checkAccess(false)` to any card that should stay visible regardless.
- **Ungrouped cards moved above the groups**, matching how Filament orders ungrouped navigation items.

If you published `pages/cards-page.blade.php`, re-publish it — the view is now split into `components/group.blade.php` and `components/card.blade.php`.

### Fixed

- **Responsive `columns()` and `columnSpan()` now actually render.** The view built its Tailwind classes by string interpolation (`"{$prefix}grid-cols-{$count}"`), and Tailwind only sees literal strings when scanning a Blade file — so none of those utilities were ever generated. A responsive `columns(['md' => 2, 'xl' => 4])` silently rendered as a single column, `columnSpan()` and `columnSpanFull()` did nothing, and a scalar `$columns` above four never reached its `2xl` step. Layout is now delegated to Filament's own `grid()`/`gridColumn()` attribute macros, whose classes ship compiled in Filament's CSS.
- **`columnSpan()` no longer drops most breakpoints.** The view only read `default` and `lg` out of the span array; `sm`, `md`, `xl` and `2xl` were discarded. `columnStart()` is now supported too.
- **`discoverResourceCards()` works.** It was gated behind `is_a(static::class, Filament\Resources\Pages\Page::class)`, which no `CardsPage` subclass can satisfy — that class is a *sibling* of `Filament\Pages\Page`, not an ancestor. The guard was always false, so the documented resource-hub page rendered blank. The resource is now read from the `$resource` property the README already told you to declare.
- **Cards added with `addCards()` no longer leak between pages.** `$appendedCards` was a plain static on the abstract base class, and PHP shares such a property with every subclass that does not redeclare it, so cards added to one hub page appeared on all of them.
- **Coloured card icons are no longer permanently tinted in dark mode.** `"group-hover:{$class}"` expanded to `group-hover:text-x-500 dark:text-x-400`, leaving the dark variant applied at rest rather than on hover.
- **Any registered panel colour works on a card.** Colours went through a hardcoded map of six names, so a custom `->color('brand')` silently lost its border. Cards now use Filament's colour pipeline, which defines the custom properties for every colour on the panel.
- **Cards for a resource's sub-pages show their own label**, not the parent resource's — a hub of Profile / Billing / Team no longer renders as three cards all labelled "Users".
- **A card with no page and no URL is no longer a dead link.** It rendered as an anchor to `#`; it now renders as plain, non-interactive content.
- Package translations are registered. `resources/lang/` shipped but was never loaded, so the search placeholder and screen-reader labels resolved against the application's own translations and could not be translated at all.
- `shouldRegisterNavigation()` and `getNavigationGroup()` are probed with `method_exists()` rather than `is_callable()`. Filament components are `Macroable`, so `is_callable()` answers true for any name and the intended fallbacks were unreachable.

### Added

- **Cards respect `canAccess()`.** A card built from a page or resource class is hidden when the current user cannot access it, so the grid never offers a link that lands on a 403. Opt out per card with `->checkAccess(false)`.
- **Actions on cards** — `CardItem::actions([Action::make('sync')->action(...)])`. Action names must be unique across the page, the same constraint Filament puts on header actions.
- **Panel-wide defaults** via the plugin: `FilamentCardsPlugin::make()->columns(4)->searchable()`. Resolution order is page static property > plugin default > package default, so existing pages are unaffected.
- **Overridable configuration getters** on `CardsPage` (`getCardsColumns()`, `isCardsSearchable()`, and friends) for settings that need to be computed at runtime.
- `CardGroup::persistCollapsed()` remembers a group's collapsed state across page loads, using the same Alpine `$persist` mechanism as Filament's own sections.
- `CardGroup::contained()` renders a group inside a Filament section instead of under a plain heading.
- `CardGroup::id()` sets a stable identifier for a group, used to key its persisted collapse state.
- Empty states for a page with no cards and for a search that matches nothing.
- Search is accent-insensitive — "Präferenzen" is findable whether or not you type the umlaut — and announces its result count to screen readers.
- `CardGroup::collapsible()`, `collapsed()` and `compact()` accept closures.
- CI: test matrix (PHP 8.2–8.4 × Laravel 11–12 × lowest/stable), PHPStan and Pint workflows.

### Changed

- **Ungrouped cards from `getCards()` now render above the groups** rather than below them, matching how Filament orders ungrouped navigation items and how `discoverClusterCards()` already behaved.
- Group headings are `<h2>` and card titles `<h3>`, instead of `<h4>`/`<h5>` under the page's `<h1>`.
- A collapsible group's toggle is a real `<button>` carrying `aria-expanded` and `aria-controls`, rather than a click handler on a `<div>` that no keyboard could reach.
- Disabled cards carry `aria-disabled`, and a card that opens in a new tab says so to screen readers.
- The search input has a label, and its clear button's text is translatable.
- Card badges and navigation descriptions resolve lazily. Cluster discovery called `getNavigationBadge()` on every component up front, running a query per card on each page load whether or not the card was visible.
- Card icons keep their original dimensions (40px at the default `Medium`). They are sized on the package's own scale rather than Filament's `fi-size-*` one, which is built for inline glyphs and tops out at 32px.
- PHPStan raised to level 6 on `src`, with an empty baseline.
- Removed the unused JS/CSS build pipeline (`bin/build.js`, `package.json`, `resources/js`, `resources/css`, `resources/dist`). Nothing registered it, the JS entrypoint was empty, and the views need no bundle — only the `@source` line already in your theme.
- **The page view was split into `components/group.blade.php` and `components/card.blade.php`.** If you published `pages/cards-page.blade.php`, re-publish it — the old copy references view data (`$pageColumns` handling, the hand-rolled grid helpers) that no longer exists.
- `getViewData()` gained `shouldPersistCollapsed` and `pageKey` keys.
- `CardItem::getUrl()` returns `?string` instead of `string` — `null` where it used to return `'#'`. Use `hasUrl()` to test for a destination.

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
