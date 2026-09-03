<?php

namespace Harvirsidhu\FilamentCards;

use Closure;
use Filament\Support\Concerns\EvaluatesClosures;
use Filament\Support\Concerns\HasExtraAttributes;
use Filament\Support\Concerns\HasIcon;
use Harvirsidhu\FilamentCards\Concerns\CanBeHidden;
use Harvirsidhu\FilamentCards\Concerns\HasDescription;
use Harvirsidhu\FilamentCards\Concerns\HasLabel;

class CardGroup
{
    use CanBeHidden;
    use EvaluatesClosures;
    use HasDescription;
    use HasExtraAttributes;
    use HasIcon;
    use HasLabel;

    /** @var array<int, CardItem> */
    protected array $schema = [];

    /** @var int|string|array<string, int|string|null>|Closure|null */
    protected int | string | array | Closure | null $columns = null;

    protected bool | Closure $isCollapsible = false;

    protected bool | Closure $isCollapsed = false;

    protected bool | Closure $isCompact = false;

    protected bool | Closure | null $shouldPersistCollapsed = null;

    protected bool | Closure $isContained = false;

    protected ?string $id = null;

    public function __construct(string | Closure | null $label = null)
    {
        $this->label = $label;
    }

    public static function make(string | Closure | null $label = null): static
    {
        return new static($label); // @phpstan-ignore-line new.static
    }

    /**
     * @param  array<int, CardItem>  $items
     */
    public function schema(array $items): static
    {
        $this->schema = $items;

        return $this;
    }

    /**
     * @param  int|string|array<string, int|string|null>|Closure|null  $columns
     */
    public function columns(int | string | array | Closure | null $columns): static
    {
        $this->columns = $columns;

        return $this;
    }

    public function collapsible(bool | Closure $condition = true): static
    {
        $this->isCollapsible = $condition;

        return $this;
    }

    public function collapsed(bool | Closure $condition = true): static
    {
        $this->isCollapsed = $condition;
        $this->isCollapsible = true;

        return $this;
    }

    /**
     * Remember whether this group is collapsed across page loads.
     *
     * Backed by Alpine's `$persist`, the same mechanism Filament's own
     * collapsible sections use, so a group the user closed stays closed
     * instead of springing open on every navigation.
     */
    public function persistCollapsed(bool | Closure $condition = true): static
    {
        $this->shouldPersistCollapsed = $condition;

        return $this;
    }

    public function compact(bool | Closure $condition = true): static
    {
        $this->isCompact = $condition;

        return $this;
    }

    /**
     * Render this group inside a Filament section rather than under a plain
     * heading. Gives the group a card-like container that matches the rest of
     * the panel, at the cost of a heavier look.
     */
    public function contained(bool | Closure $condition = true): static
    {
        $this->isContained = $condition;

        return $this;
    }

    /**
     * A stable identifier, used to key the group's persisted collapse state.
     *
     * Defaults to a slug of the label so that two pages with a "Billing"
     * group do not fight over the same stored value once the page name is
     * mixed in by the view.
     */
    public function id(?string $id): static
    {
        $this->id = $id;

        return $this;
    }

    public function getId(): string
    {
        if (filled($this->id)) {
            return $this->id;
        }

        $label = $this->getLabel();

        return filled($label)
            ? str(strip_tags((string) $label))->slug()->toString()
            : 'ungrouped';
    }

    public function isCollapsible(): bool
    {
        return (bool) $this->evaluate($this->isCollapsible);
    }

    public function isCollapsed(): bool
    {
        return (bool) $this->evaluate($this->isCollapsed);
    }

    public function shouldPersistCollapsed(): ?bool
    {
        $shouldPersist = $this->evaluate($this->shouldPersistCollapsed);

        return $shouldPersist === null ? null : (bool) $shouldPersist;
    }

    public function isCompact(): bool
    {
        return (bool) $this->evaluate($this->isCompact);
    }

    public function isContained(): bool
    {
        return (bool) $this->evaluate($this->isContained);
    }

    /**
     * @return int|string|array<string, int|string|null>|null
     */
    public function getColumns(): int | string | array | null
    {
        return $this->evaluate($this->columns);
    }

    /**
     * @return array<int, CardItem>
     */
    public function getItems(): array
    {
        return collect($this->schema)
            ->filter(fn (CardItem $item): bool => $item->isVisible())
            ->sortBy(fn (CardItem $item): int => $item->getSort())
            ->values()
            ->all();
    }

    public function hasItems(): bool
    {
        return $this->getItems() !== [];
    }
}
