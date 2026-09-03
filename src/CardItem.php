<?php

namespace Harvirsidhu\FilamentCards;

use BackedEnum;
use Closure;
use Filament\Actions\Action;
use Filament\Actions\Concerns\CanBeSorted;
use Filament\Pages\Page;
use Filament\Resources\Resource;
use Filament\Support\Concerns\CanSpanColumns;
use Filament\Support\Concerns\EvaluatesClosures;
use Filament\Support\Concerns\HasColor;
use Filament\Support\Concerns\HasExtraAttributes;
use Filament\Support\Concerns\HasIcon;
use Filament\Support\Enums\Alignment;
use Harvirsidhu\FilamentCards\Concerns\CanBeDisabled;
use Harvirsidhu\FilamentCards\Concerns\CanBeHidden;
use Harvirsidhu\FilamentCards\Concerns\HasDescription;
use Harvirsidhu\FilamentCards\Concerns\HasLabel;
use Illuminate\Contracts\Support\Htmlable;

class CardItem
{
    use CanBeDisabled;
    use CanBeHidden;
    use CanBeSorted;
    use CanSpanColumns;
    use EvaluatesClosures;
    use HasColor;
    use HasDescription;
    use HasExtraAttributes;
    use HasIcon;
    use HasLabel;

    protected bool | Closure $openUrlInNewTab = false;

    protected Alignment | string | Closure | null $alignment = null;

    protected string | Htmlable | Closure | null $badge = null;

    /** @var string|array<int|string, string>|Closure|null */
    protected string | array | Closure | null $badgeColor = null;

    /** @var string|array<int, string>|Closure|null */
    protected string | array | Closure | null $searchKeywords = null;

    protected bool | Closure $shouldCheckAccess = true;

    /** @var array<int, Action> */
    protected array $actions = [];

    /**
     * @param  class-string|null  $page
     */
    public function __construct(protected ?string $page = null, protected string | Closure | null $url = null) {}

    public static function make(string $pageClassOrUrl): static
    {
        if (class_exists($pageClassOrUrl) && (is_a($pageClassOrUrl, Page::class, true) || is_a($pageClassOrUrl, Resource::class, true))) {
            return new static(page: $pageClassOrUrl); // @phpstan-ignore-line new.static
        }

        return new static(url: $pageClassOrUrl); // @phpstan-ignore-line new.static
    }

    public function alignment(Alignment | string | Closure | null $alignment): static
    {
        $this->alignment = $alignment;

        return $this;
    }

    public function getAlignment(): Alignment | string | null
    {
        return $this->evaluate($this->alignment);
    }

    public function url(string | Closure | null $url): static
    {
        $this->url = $url;

        return $this;
    }

    public function openUrlInNewTab(bool | Closure $condition = true): static
    {
        $this->openUrlInNewTab = $condition;

        return $this;
    }

    public function shouldOpenUrlInNewTab(): bool
    {
        return (bool) $this->evaluate($this->openUrlInNewTab);
    }

    /**
     * @return class-string|null
     */
    public function getPage(): ?string
    {
        return $this->page;
    }

    /**
     * Opt a card out of the automatic `canAccess()` check.
     *
     * A card built from a page or resource class is hidden when the current
     * user cannot access that class, so the grid never offers a link that
     * lands on a 403. Turn this off for the rare card that points at a page
     * guarded by something Filament cannot see from `canAccess()`.
     */
    public function checkAccess(bool | Closure $condition = true): static
    {
        $this->shouldCheckAccess = $condition;

        return $this;
    }

    public function shouldCheckAccess(): bool
    {
        return (bool) $this->evaluate($this->shouldCheckAccess);
    }

    /**
     * Whether the current user may open this card's destination.
     *
     * Cards with a plain URL have nothing to check, so they are always
     * considered accessible.
     */
    public function canAccess(): bool
    {
        if ($this->page === null) {
            return true;
        }

        if (! $this->shouldCheckAccess()) {
            return true;
        }

        if (! method_exists($this->page, 'canAccess')) {
            return true;
        }

        return (bool) $this->page::canAccess();
    }

    /**
     * A card is visible only when it is both un-hidden and reachable — the
     * access check is folded in here so every render path gets it, rather
     * than only the discovery helpers that happened to call `canAccess()`.
     */
    public function isVisible(): bool
    {
        if ($this->isHidden()) {
            return false;
        }

        return $this->canAccess();
    }

    /**
     * Attach Filament actions to this card.
     *
     * Action names must be unique across the page, the same constraint
     * Filament puts on a page's header actions — the page caches them by
     * name so Livewire can mount them.
     *
     * @param  array<int, Action>  $actions
     */
    public function actions(array $actions): static
    {
        $this->actions = $actions;

        return $this;
    }

    /**
     * @return array<int, Action>
     */
    public function getActions(): array
    {
        return array_values(array_filter(
            $this->actions,
            fn (Action $action): bool => $action->isVisible(),
        ));
    }

    public function hasActions(): bool
    {
        return $this->getActions() !== [];
    }

    public function badge(string | Htmlable | Closure | null $badge): static
    {
        $this->badge = $badge;

        return $this;
    }

    public function getBadge(): string | Htmlable | null
    {
        $badge = $this->evaluate($this->badge);

        if (filled($badge)) {
            return $badge;
        }

        if ($this->page !== null) {
            if (method_exists($this->page, 'getNavigationBadge')) {
                $badge = $this->page::getNavigationBadge();

                if (filled($badge)) {
                    return $badge;
                }
            }

            if (($resource = $this->getPageResource()) && method_exists($resource, 'getNavigationBadge')) {
                return $resource::getNavigationBadge();
            }
        }

        return null;
    }

    /**
     * @param  string|array<int|string, string>|Closure|null  $badgeColor
     */
    public function badgeColor(string | array | Closure | null $badgeColor): static
    {
        $this->badgeColor = $badgeColor;

        return $this;
    }

    /**
     * @return string|array<int|string, string>|null
     */
    public function getBadgeColor(): string | array | null
    {
        $badgeColor = $this->evaluate($this->badgeColor);

        if (filled($badgeColor)) {
            return $badgeColor;
        }

        if ($this->page !== null) {
            if (method_exists($this->page, 'getNavigationBadgeColor')) {
                $badgeColor = $this->page::getNavigationBadgeColor();

                if (filled($badgeColor)) {
                    return $badgeColor;
                }
            }

            if (($resource = $this->getPageResource()) && method_exists($resource, 'getNavigationBadgeColor')) {
                return $resource::getNavigationBadgeColor();
            }
        }

        return null;
    }

    /**
     * @param  string|array<int, string>|Closure|null  $keywords
     */
    public function searchKeywords(string | array | Closure | null $keywords): static
    {
        $this->searchKeywords = $keywords;

        return $this;
    }

    /**
     * @return array<int, string>
     */
    public function getSearchKeywords(): array
    {
        $keywords = $this->evaluate($this->searchKeywords);

        if (blank($keywords) && $this->page !== null) {
            if (method_exists($this->page, 'getFilamentCardsSearchKeywords')) {
                $keywords = $this->page::getFilamentCardsSearchKeywords();
            } elseif (property_exists($this->page, 'filamentCardsSearchKeywords')) {
                $keywords = $this->page::$filamentCardsSearchKeywords;
            }
        }

        if (blank($keywords)) {
            return [];
        }

        return array_values(array_filter(
            array_map(trim(...), is_array($keywords) ? $keywords : [$keywords]),
            fn (string $keyword): bool => $keyword !== '',
        ));
    }

    public function getLabel(): string | Htmlable | null
    {
        $label = $this->evaluate($this->label);

        if (filled($label)) {
            return $label;
        }

        if ($this->page === null) {
            return null;
        }

        // The page's own navigation label comes first. Deferring to the
        // resource ahead of it made every card for a resource's sub-page
        // show the resource's name, so a hub of "Profile / Billing / Team"
        // rendered as three cards all labelled "Users".
        $label = $this->page::getNavigationLabel();

        if (filled($label)) {
            return $label;
        }

        if ($resource = $this->getPageResource()) {
            return $resource::getNavigationLabel();
        }

        return null;
    }

    public function getDescription(): string | Htmlable | null
    {
        $description = $this->evaluate($this->description);

        if (filled($description)) {
            return $description;
        }

        if ($this->page === null) {
            return null;
        }

        if (method_exists($this->page, 'getNavigationDescription')) {
            return $this->page::getNavigationDescription();
        }

        if (property_exists($this->page, 'navigationDescription')) {
            return $this->page::$navigationDescription;
        }

        return null;
    }

    public function getIcon(string | BackedEnum | Htmlable | null $default = null): string | BackedEnum | Htmlable | null
    {
        $icon = $this->evaluate($this->icon);

        if (filled($icon)) {
            return $icon;
        }

        if ($this->page !== null) {
            $icon = $this->page::getNavigationIcon();

            if (filled($icon)) {
                return $icon;
            }

            if ($resource = $this->getPageResource()) {
                return $resource::getNavigationIcon();
            }
        }

        return $default;
    }

    /**
     * Whether this card actually points somewhere.
     *
     * A card with neither a page nor a URL used to render as an anchor to
     * `#`, which looks clickable and goes nowhere; the view now renders it as
     * plain, non-interactive content instead.
     */
    public function hasUrl(): bool
    {
        return filled($this->getUrl());
    }

    public function getUrl(): ?string
    {
        $url = $this->evaluate($this->url);

        if (filled($url)) {
            return $url;
        }

        if ($this->page !== null) {
            return $this->page::getUrl();
        }

        return null;
    }

    /**
     * @return class-string|null
     */
    protected function getPageResource(): ?string
    {
        if ($this->page === null) {
            return null;
        }

        if (is_a($this->page, Resource::class, true)) {
            return $this->page;
        }

        return method_exists($this->page, 'getResource')
            ? $this->page::getResource()
            : null;
    }
}
