<?php

namespace Harvirsidhu\FilamentCards\Filament\Pages;

use Filament\Clusters\Cluster;
use Filament\Facades\Filament;
use Filament\Pages\Page;
use Filament\Resources\Pages\Page as ResourcePage;
use Filament\Resources\Resource;
use Filament\Support\Enums\Alignment;
use Filament\Support\Enums\IconPosition;
use Filament\Support\Enums\IconSize;
use Harvirsidhu\FilamentCards\CardGroup;
use Harvirsidhu\FilamentCards\CardItem;
use Illuminate\Support\Collection;

abstract class CardsPage extends Page
{
    protected string $view = 'harvirsidhu-filament-cards::pages.cards-page';

    protected static int $columns = 3;

    protected static Alignment $itemsAlignment = Alignment::Start;

    protected static bool $iconInlined = false;

    protected static IconSize $iconSize = IconSize::Medium;

    protected static IconPosition $iconPosition = IconPosition::Before;

    /** @var array<class-string> */
    protected static array $excludedClusterComponents = [];

    /** @var array<class-string> */
    protected static array $excludedResourcePages = [];

    /** @var array<CardGroup|CardItem> */
    protected static array $appendedCards = [];

    /**
     * Override this method to define the cards displayed on the page.
     *
     * @return array<CardGroup|CardItem>
     */
    protected static function getCards(): array
    {
        return [];
    }

    /**
     * Dynamically add cards from outside the class (e.g., from service providers).
     *
     * @param  array<CardGroup|CardItem>  $cards
     */
    public static function addCards(array $cards): void
    {
        static::$appendedCards = [
            ...static::$appendedCards,
            ...$cards,
        ];
    }

    /**
     * Auto-discover all pages and resources in the same Cluster and create CardItems.
     * Groups cards by $navigationGroup, sorts by $navigationSort.
     *
     * @return array<CardGroup|CardItem>
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
                if (is_a($component, Resource::class, true)) {
                    return $component::canAccess()
                        && static::shouldIncludeClusterComponent($component);
                }

                if (is_a($component, Page::class, true)) {
                    return $component::canAccess()
                        && static::shouldIncludeClusterComponent($component);
                }

                return false;
            })
            ->map(function (string $component): CardItem {
                $item = CardItem::make($component);

                if (is_a($component, Resource::class, true)) {
                    $item
                        ->label($component::getNavigationLabel())
                        ->description(static::getNavigationDescription($component))
                        ->icon($component::getNavigationIcon())
                        ->sort($component::getNavigationSort() ?? 0)
                        ->url($component::getUrl());
                }

                if (is_a($component, Page::class, true) && ! is_a($component, Resource::class, true)) {
                    $item
                        ->description(static::getNavigationDescription($component))
                        ->sort($component::getNavigationSort() ?? 0);
                }

                return $item;
            });

        return static::groupItemsByNavigation($items);
    }

    /**
     * Auto-discover all pages registered on the parent Resource and create CardItems.
     *
     * @return array<CardGroup|CardItem>
     */
    public static function discoverResourceCards(): array
    {
        if (! is_a(static::class, ResourcePage::class, true)) {
            return [];
        }

        $resource = static::getResource();

        if ($resource === null) {
            return [];
        }

        $items = collect($resource::getPages())
            ->filter(function ($pageRegistration) {
                $pageClass = $pageRegistration->getPage();

                return $pageClass !== static::class
                    && is_a($pageClass, Page::class, true)
                    && $pageClass::canAccess()
                    && static::shouldIncludeResourcePage($pageClass);
            })
            ->map(function ($pageRegistration): CardItem {
                $pageClass = $pageRegistration->getPage();

                return CardItem::make($pageClass)
                    ->description(static::getNavigationDescription($pageClass))
                    ->sort($pageClass::getNavigationSort() ?? 0);
            });

        return static::groupItemsByNavigation($items);
    }

    /**
     * Try to get a description from the page/resource class.
     * Checks for a static $navigationDescription property or getNavigationDescription() method.
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
     * Group a collection of CardItems by their page's $navigationGroup.
     *
     * @return array<CardGroup|CardItem>
     */
    protected static function groupItemsByNavigation(Collection $items): array
    {
        $grouped = $items->groupBy(function (CardItem $item): ?string {
            $page = $item->getPage();

            if ($page === null) {
                return null;
            }

            if (is_a($page, Resource::class, true) || is_a($page, Page::class, true)) {
                return static::resolveComponentCardsGroup($page);
            }

            return null;
        });

        $result = [];

        foreach ($grouped as $groupName => $groupItems) {
            $sortedItems = $groupItems
                ->sortBy(fn (CardItem $item): int => $item->getSort())
                ->values()
                ->all();

            if (filled($groupName)) {
                $result[] = CardGroup::make($groupName)
                    ->schema($sortedItems);
            } else {
                foreach ($sortedItems as $item) {
                    $result[] = $item;
                }
            }
        }

        return $result;
    }

    protected static function getClusterClass(): ?string
    {
        $cluster = static::$cluster ?? null;

        if ($cluster !== null && is_a($cluster, Cluster::class, true)) {
            return $cluster;
        }

        return null;
    }

    /**
     * Whether a discovered component should be included in cluster cards.
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
     */
    protected static function shouldIncludeDiscoveredCard(string $component): bool
    {
        return static::resolveComponentCardsVisibility($component);
    }

    /**
     * Resolves visibility from component-level settings.
     * Supports showInFilamentCards() or static $showInFilamentCards.
     * Falls back to shouldRegisterNavigation() when available.
     */
    protected static function resolveComponentCardsVisibility(string $component): bool
    {
        if (method_exists($component, 'showInFilamentCards')) {
            return (bool) $component::showInFilamentCards();
        }

        if (property_exists($component, 'showInFilamentCards')) {
            return (bool) $component::$showInFilamentCards;
        }

        if (is_callable([$component, 'shouldRegisterNavigation'])) {
            return (bool) $component::shouldRegisterNavigation();
        }

        return true;
    }

    /**
     * Resolves group name for discovered cards.
     * Supports getFilamentCardsGroup() or static $filamentCardsGroup.
     * Falls back to getNavigationGroup() when available.
     */
    protected static function resolveComponentCardsGroup(string $component): ?string
    {
        if (method_exists($component, 'getFilamentCardsGroup')) {
            return $component::getFilamentCardsGroup();
        }

        if (property_exists($component, 'filamentCardsGroup')) {
            return $component::$filamentCardsGroup;
        }

        if (is_callable([$component, 'getNavigationGroup'])) {
            return $component::getNavigationGroup();
        }

        return null;
    }

    protected function getViewData(): array
    {
        return [
            'groups' => $this->getProcessedGroups(),
            'pageColumns' => static::$columns,
            'alignment' => static::$itemsAlignment,
            'isIconInlined' => static::$iconInlined,
            'iconSize' => static::$iconSize,
            'iconPosition' => static::$iconPosition,
        ];
    }

    /**
     * Process all cards into a normalized collection of CardGroups.
     *
     * @return Collection<int, CardGroup>
     */
    protected function getProcessedGroups(): Collection
    {
        $cards = [
            ...static::getCards(),
            ...static::$appendedCards,
        ];

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

        if (! empty($ungroupedItems)) {
            $groups->push(
                CardGroup::make(null)->schema($ungroupedItems)
            );
        }

        return $groups;
    }
}
