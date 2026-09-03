<?php

namespace Harvirsidhu\FilamentCards\Filament\Pages;

use Filament\Clusters\Cluster;
use Filament\Facades\Filament;
use Filament\Pages\Page;
use Filament\Resources\Resource;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Enums\Alignment;
use Filament\Support\Enums\IconPosition;
use Filament\Support\Enums\IconSize;
use Harvirsidhu\FilamentCards\CardGroup;
use Harvirsidhu\FilamentCards\CardItem;
use Harvirsidhu\FilamentCards\Concerns\InteractsWithCardActions;
use Harvirsidhu\FilamentCards\FilamentCardsPlugin;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Collection;
use ReflectionProperty;
use UnitEnum;

abstract class CardsPage extends Page
{
    use InteractsWithCardActions;

    protected string $view = 'harvirsidhu-filament-cards::pages.cards-page';

    /** @var int|string|array<string, int|string|null> */
    protected static int | string | array $columns = 3;

    protected static Alignment $itemsAlignment = Alignment::Center;

    protected static bool $iconInlined = false;

    protected static IconSize $iconSize = IconSize::Medium;

    protected static IconPosition $iconPosition = IconPosition::Before;

    protected static bool $searchable = false;

    protected static ?string $searchPlaceholder = null;

    /** @var array<int, class-string> */
    protected static array $excludedClusterComponents = [];

    /** @var array<int, class-string> */
    protected static array $excludedResourcePages = [];

    /**
     * Cards appended at runtime, keyed by the page class they were added to.
     *
     * A plain `static::$appendedCards = [...]` writes through to the property
     * declared here on the base class, which PHP shares with every subclass
     * that does not redeclare it — so cards added to one cards page appeared
     * on all of them. Keying by class keeps each page's additions its own.
     *
     * @var array<class-string, array<int, CardGroup|CardItem>>
     */
    protected static array $appendedCards = [];

    /**
     * Whether a page property was redeclared by a subclass.
     *
     * This is what lets plugin-level defaults exist without breaking anyone:
     * the static properties cannot be made nullable (PHP requires a
     * redeclared static property to keep the parent's exact type, and the
     * documented usage is `protected static bool $searchable = true`), so
     * "did the page set this?" has to be answered by asking where the
     * property is declared rather than by comparing it against null.
     *
     * @var array<class-string, array<string, bool>>
     */
    protected static array $overriddenPageProperties = [];

    /**
     * Built once per request: boot() needs the groups to register card
     * actions and render needs them again for the view. Without this every
     * visibility closure and canAccess() policy check ran twice.
     *
     * @var Collection<int, CardGroup>|null
     */
    protected ?Collection $processedGroups = null;

    /**
     * Override this method to define the cards displayed on the page.
     *
     * @return array<int, CardGroup|CardItem>
     */
    protected static function getCards(): array
    {
        return [];
    }

    /**
     * Dynamically add cards from outside the class (e.g., from service providers).
     *
     * @param  array<int, CardGroup|CardItem>  $cards
     */
    public static function addCards(array $cards): void
    {
        static::$appendedCards[static::class] = [
            ...(static::$appendedCards[static::class] ?? []),
            ...$cards,
        ];
    }

    /**
     * @return array<int, CardGroup|CardItem>
     */
    public static function getAppendedCards(): array
    {
        return static::$appendedCards[static::class] ?? [];
    }

    /**
     * Discard cards added at runtime. Mostly useful in tests, where a page
     * class outlives a single request.
     */
    public static function flushAppendedCards(): void
    {
        unset(static::$appendedCards[static::class]);
    }

    protected static function hasOverriddenPageProperty(string $property): bool
    {
        if (isset(static::$overriddenPageProperties[static::class][$property])) {
            return static::$overriddenPageProperties[static::class][$property];
        }

        if (! property_exists(static::class, $property)) {
            return static::$overriddenPageProperties[static::class][$property] = false;
        }

        $declaringClass = (new ReflectionProperty(static::class, $property))
            ->getDeclaringClass()
            ->getName();

        return static::$overriddenPageProperties[static::class][$property] = $declaringClass !== self::class;
    }

    /**
     * @return int|string|array<string, int|string|null>
     */
    public static function getCardsColumns(): int | string | array
    {
        if (static::hasOverriddenPageProperty('columns')) {
            return static::$columns;
        }

        return FilamentCardsPlugin::getCurrent()?->getColumns() ?? static::$columns;
    }

    public static function getCardsItemsAlignment(): Alignment | string
    {
        if (static::hasOverriddenPageProperty('itemsAlignment')) {
            return static::$itemsAlignment;
        }

        return FilamentCardsPlugin::getCurrent()?->getItemsAlignment() ?? static::$itemsAlignment;
    }

    public static function getCardsIconSize(): IconSize | string
    {
        if (static::hasOverriddenPageProperty('iconSize')) {
            return static::$iconSize;
        }

        return FilamentCardsPlugin::getCurrent()?->getIconSize() ?? static::$iconSize;
    }

    public static function getCardsIconPosition(): IconPosition | string
    {
        if (static::hasOverriddenPageProperty('iconPosition')) {
            return static::$iconPosition;
        }

        return FilamentCardsPlugin::getCurrent()?->getIconPosition() ?? static::$iconPosition;
    }

    public static function hasInlinedCardIcons(): bool
    {
        if (static::hasOverriddenPageProperty('iconInlined')) {
            return static::$iconInlined;
        }

        return FilamentCardsPlugin::getCurrent()?->isIconInlined() ?? static::$iconInlined;
    }

    public static function isCardsSearchable(): bool
    {
        if (static::hasOverriddenPageProperty('searchable')) {
            return static::$searchable;
        }

        return FilamentCardsPlugin::getCurrent()?->isSearchable() ?? static::$searchable;
    }

    public static function getCardsSearchPlaceholder(): string
    {
        if (static::hasOverriddenPageProperty('searchPlaceholder') && filled(static::$searchPlaceholder)) {
            return static::$searchPlaceholder;
        }

        return FilamentCardsPlugin::getCurrent()?->getSearchPlaceholder()
            ?? static::$searchPlaceholder
            ?? __('filament-cards::cards.search.placeholder');
    }

    public static function shouldPersistCardsCollapsed(): bool
    {
        return FilamentCardsPlugin::getCurrent()?->shouldPersistCollapsed() ?? false;
    }

    /**
     * Auto-discover all pages and resources in the same Cluster and create CardItems.
     * Groups cards by $navigationGroup, sorts by $navigationSort.
     *
     * @return array<int, CardGroup|CardItem>
     */
    public static function discoverClusterCards(): array
    {
        $cluster = static::getClusterClass();

        if ($cluster === null) {
            return [];
        }

        $components = Filament::getCurrentPanel()
            ?->getClusteredComponents($cluster) ?? [];

        $items = collect($components)
            ->reject(fn (string $component): bool => $component === static::class)
            ->filter(function (string $component): bool {
                if (! is_a($component, Resource::class, true) && ! is_a($component, Page::class, true)) {
                    return false;
                }

                // Access is checked lazily by CardItem::isVisible(), so it is
                // not repeated here.
                return static::shouldIncludeClusterComponent($component);
            })
            /**
             * Label, description, icon, badge and URL are deliberately not
             * copied onto the item here. CardItem already falls back to the
             * component's navigation methods when its own value is blank, so
             * setting them eagerly only duplicated that logic — and, for
             * badges that run a count query, forced a query per discovered
             * component on every page load whether or not the card was
             * ultimately visible.
             */
            ->map(fn (string $component): CardItem => CardItem::make($component)
                ->sort($component::getNavigationSort() ?? 0));

        return static::groupItemsByNavigation($items);
    }

    /**
     * Auto-discover all pages registered on the parent Resource and create CardItems.
     *
     * @return array<int, CardGroup|CardItem>
     */
    public static function discoverResourceCards(): array
    {
        $resource = static::getCardsResource();

        if ($resource === null) {
            return [];
        }

        $items = collect($resource::getPages())
            ->filter(function (object $pageRegistration): bool {
                $pageClass = $pageRegistration->getPage();

                return $pageClass !== static::class
                    && is_a($pageClass, Page::class, true)
                    && static::shouldIncludeResourcePage($pageClass);
            })
            ->map(function (object $pageRegistration): CardItem {
                /** @var class-string<Page> $pageClass */
                $pageClass = $pageRegistration->getPage();

                return CardItem::make($pageClass)
                    ->sort($pageClass::getNavigationSort() ?? 0);
            });

        return static::groupItemsByNavigation($items);
    }

    /**
     * The resource whose pages this page turns into cards.
     *
     * This used to be gated behind `is_a(static::class, ResourcePage::class)`,
     * which could never be true: CardsPage extends Filament\Pages\Page, and
     * Filament\Resources\Pages\Page is a *sibling* of that class rather than
     * an ancestor. Every call to discoverResourceCards() therefore returned an
     * empty array, and the documented resource-hub page rendered blank.
     *
     * The property is read reflectively because it cannot be declared here:
     * the documented usage is `protected static string $resource = X::class`,
     * and PHP requires a redeclared static property to match the parent's type
     * exactly, so declaring `?string` on this class would fatal-error every
     * page that follows the README.
     *
     * @return class-string<\Filament\Resources\Resource>|null
     */
    protected static function getCardsResource(): ?string
    {
        $resource = null;

        if (property_exists(static::class, 'resource')) {
            /** @phpstan-ignore-next-line staticProperty.notFound */
            $resource = static::$resource;
        }

        // method_exists, not is_callable: Filament pages are Macroable, so
        // is_callable() is true for every name via __callStatic().
        if ($resource === null && method_exists(static::class, 'getResource')) {
            $resource = static::getResource();
        }

        if (! is_string($resource) || ! is_a($resource, Resource::class, true)) {
            return null;
        }

        return $resource;
    }

    /**
     * Try to get a description from the page/resource class.
     * Checks for a static $navigationDescription property or getNavigationDescription() method.
     *
     * @param  class-string  $class
     */
    protected static function getNavigationDescription(string $class): ?string
    {
        if (method_exists($class, 'getNavigationDescription')) {
            return $class::getNavigationDescription();
        }

        if (property_exists($class, 'navigationDescription')) {
            return $class::$navigationDescription;
        }

        return null;
    }

    /**
     * Try to get a navigation badge from the page/resource class.
     *
     * @param  class-string  $class
     */
    protected static function resolveDiscoveredNavigationBadge(string $class): string | Htmlable | null
    {
        if (method_exists($class, 'getNavigationBadge')) {
            return $class::getNavigationBadge();
        }

        return null;
    }

    /**
     * Try to get a navigation badge color from the page/resource class.
     *
     * @param  class-string  $class
     * @return string|array<int|string, string>|null
     */
    protected static function resolveDiscoveredNavigationBadgeColor(string $class): string | array | null
    {
        if (method_exists($class, 'getNavigationBadgeColor')) {
            return $class::getNavigationBadgeColor();
        }

        return null;
    }

    /**
     * Group a collection of CardItems by their page's $navigationGroup,
     * ordering both the groups and the cards within them the way Filament
     * orders the sidebar.
     *
     * Sorting happens BEFORE grouping, which is what puts the groups in the
     * right order: groupBy() preserves the order keys are first encountered,
     * so a pre-sorted collection yields groups ordered by their lowest
     * $navigationSort — exactly how Filament derives sidebar group order.
     *
     * Sorting only within each group (the previous behaviour) left group order
     * at the mercy of component discovery, which is filesystem order. A cluster
     * whose sidebar read Profile → Communications → People & Access could show
     * its cards as Inventory → Billing → Profile, with no visible logic.
     *
     * @param  Collection<array-key, CardItem>  $items
     * @return array<int, CardGroup|CardItem>
     */
    protected static function groupItemsByNavigation(Collection $items): array
    {
        $groupOf = function (CardItem $item): string {
            $page = $item->getPage();

            if ($page === null) {
                return '';
            }

            if (is_a($page, Resource::class, true) || is_a($page, Page::class, true)) {
                return (string) static::resolveComponentCardsGroup($page);
            }

            return '';
        };

        // An explicit comparator, not sortBy(): the group name is a tie-break
        // (PHP's sort is not stable, so equal sorts would otherwise fall back
        // to discovery order and differ between machines), and $navigationSort
        // is routinely negative — so any zero-padded string key would misorder.
        $sorted = $items->sort(fn (CardItem $a, CardItem $b): int => [$a->getSort(), $groupOf($a)]
            <=> [$b->getSort(), $groupOf($b)]);

        $ungrouped = [];
        $groups = [];

        /** @var array<string, array<int, CardItem>> $grouped */
        $grouped = $sorted->groupBy($groupOf)->map(fn (Collection $group): array => $group->values()->all())->all();

        foreach ($grouped as $groupName => $groupItems) {
            if (blank($groupName)) {
                // Filament renders ungrouped navigation items above the groups.
                // Emitting them inline would drop a lone card between two
                // group headings, where it reads as part of the wrong group.
                $ungrouped = [...$ungrouped, ...$groupItems];

                continue;
            }

            $groups[] = CardGroup::make($groupName)
                ->schema($groupItems);
        }

        return [...$ungrouped, ...$groups];
    }

    /**
     * @return class-string<Cluster>|null
     */
    protected static function getClusterClass(): ?string
    {
        return static::$cluster ?? null;
    }

    /**
     * Whether a discovered component should be included in cluster cards.
     *
     * @param  class-string  $component
     */
    protected static function shouldIncludeClusterComponent(string $component): bool
    {
        if (in_array($component, static::$excludedClusterComponents, true)) {
            return false;
        }

        return static::shouldIncludeDiscoveredCard($component);
    }

    /**
     * Whether a discovered page should be included in resource cards.
     *
     * @param  class-string  $pageClass
     */
    protected static function shouldIncludeResourcePage(string $pageClass): bool
    {
        if (in_array($pageClass, static::$excludedResourcePages, true)) {
            return false;
        }

        return static::shouldIncludeDiscoveredCard($pageClass);
    }

    /**
     * Override to customize inclusion logic for all discovered cards.
     *
     * @param  class-string  $component
     */
    protected static function shouldIncludeDiscoveredCard(string $component): bool
    {
        return static::resolveComponentCardsVisibility($component);
    }

    /**
     * Resolves visibility from component-level settings.
     * Supports showInFilamentCards() or static $showInFilamentCards.
     * Falls back to shouldRegisterNavigation() when available.
     *
     * @param  class-string  $component
     */
    protected static function resolveComponentCardsVisibility(string $component): bool
    {
        if (method_exists($component, 'showInFilamentCards')) {
            return (bool) $component::showInFilamentCards();
        }

        if (property_exists($component, 'showInFilamentCards')) {
            return (bool) $component::$showInFilamentCards;
        }

        // method_exists, not is_callable: Filament components are Macroable,
        // so is_callable() answers true for any name at all and the fallback
        // below was unreachable.
        if (method_exists($component, 'shouldRegisterNavigation')) {
            return (bool) $component::shouldRegisterNavigation();
        }

        return true;
    }

    /**
     * Resolves group name for discovered cards.
     * Supports getFilamentCardsGroup() or static $filamentCardsGroup.
     * Falls back to getNavigationGroup() when available.
     *
     * @param  class-string  $component
     */
    protected static function resolveComponentCardsGroup(string $component): ?string
    {
        if (method_exists($component, 'getFilamentCardsGroup')) {
            return $component::getFilamentCardsGroup();
        }

        if (property_exists($component, 'filamentCardsGroup')) {
            return $component::$filamentCardsGroup;
        }

        if (method_exists($component, 'getNavigationGroup')) {
            $group = $component::getNavigationGroup();

            // Filament 4 allows a backed enum as a navigation group.
            // Mirror Filament's own handling of enum navigation groups: the
            // sidebar shows the enum's label when it has one and falls back
            // to the case name — never the backing value.
            if ($group instanceof HasLabel) {
                return $group->getLabel();
            }

            if ($group instanceof UnitEnum) {
                return $group->name;
            }

            return $group;
        }

        return null;
    }

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        return [
            'groups' => $this->getProcessedGroups(),
            'pageColumns' => static::getCardsColumns(),
            'alignment' => static::getCardsItemsAlignment(),
            'isIconInlined' => static::hasInlinedCardIcons(),
            'iconSize' => static::getCardsIconSize(),
            'iconPosition' => static::getCardsIconPosition(),
            'isSearchable' => static::isCardsSearchable(),
            'searchPlaceholder' => static::getCardsSearchPlaceholder(),
            'shouldPersistCollapsed' => static::shouldPersistCardsCollapsed(),
            'pageKey' => str(static::class)->slug()->toString(),
        ];
    }

    /**
     * Process all cards into a normalized collection of CardGroups.
     *
     * @return Collection<int, CardGroup>
     */
    protected function getProcessedGroups(): Collection
    {
        if ($this->processedGroups instanceof \Illuminate\Support\Collection) {
            return $this->processedGroups;
        }

        $cards = [
            ...static::getCards(),
            ...static::getAppendedCards(),
        ];

        /** @var Collection<int, CardGroup> $groups */
        $groups = collect();
        $ungroupedItems = [];

        foreach ($cards as $card) {
            if ($card instanceof CardGroup) {
                if ($card->isVisible()) {
                    $groups->push($card);
                }
            } elseif ($card instanceof CardItem) {
                if ($card->isVisible()) {
                    $ungroupedItems[] = $card;
                }
            }
        }

        if ($ungroupedItems !== []) {
            // Ungrouped cards render above the groups, matching how Filament
            // orders ungrouped navigation items in the sidebar.
            $groups->prepend(
                CardGroup::make()->schema($ungroupedItems)
            );
        }

        return $this->processedGroups = $groups->values();
    }
}
