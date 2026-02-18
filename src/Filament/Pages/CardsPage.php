<?php

namespace Harvirsidhu\FilamentCards\Filament\Pages;

use Filament\Clusters\Cluster;
use Filament\Facades\Filament;
use Filament\Pages\Page;
use Filament\Resources\Pages\Page as ResourcePage;
use Filament\Resources\Resource;
use Filament\Support\Enums\IconSize;
use Harvirsidhu\FilamentCards\CardGroup;
use Harvirsidhu\FilamentCards\CardItem;
use Harvirsidhu\FilamentCards\Enums\Alignment;
use Illuminate\Support\Collection;

abstract class CardsPage extends Page
{
    protected static string $view = 'filament-cards::pages.cards-page';

    protected static int $columns = 3;

    protected static Alignment $itemsAlignment = Alignment::Start;

    protected static bool $iconInlined = false;

    protected static IconSize $iconSize = IconSize::Medium;

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
                    return $component::canAccess();
                }

                if (is_a($component, Page::class, true)) {
                    return $component::canAccess();
                }

                return false;
            })
            ->map(function (string $component): CardItem {
                $item = CardItem::make($component);

                if (is_a($component, Resource::class, true)) {
                    $item
                        ->label($component::getNavigationLabel())
                        ->icon($component::getNavigationIcon())
                        ->sort($component::getNavigationSort() ?? 0)
                        ->url($component::getUrl());
                }

                if (is_a($component, Page::class, true) && ! is_a($component, Resource::class, true)) {
                    $item->sort($component::getNavigationSort() ?? 0);
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
                    && $pageClass::canAccess();
            })
            ->map(function ($pageRegistration): CardItem {
                $pageClass = $pageRegistration->getPage();

                return CardItem::make($pageClass)
                    ->sort($pageClass::getNavigationSort() ?? 0);
            });

        return static::groupItemsByNavigation($items);
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

            if (is_a($page, Resource::class, true)) {
                return $page::getNavigationGroup();
            }

            if (is_a($page, Page::class, true)) {
                return $page::getNavigationGroup();
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

    protected function getViewData(): array
    {
        return [
            'groups' => $this->getProcessedGroups(),
            'pageColumns' => static::$columns,
            'alignment' => static::$itemsAlignment,
            'isIconInlined' => static::$iconInlined,
            'iconSize' => static::$iconSize,
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
