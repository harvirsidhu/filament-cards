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

    /** @var array<CardItem> */
    protected array $schema = [];

    protected int | Closure | null $columns = null;

    protected bool $isCollapsible = false;

    protected bool | Closure $isCollapsed = false;

    protected bool $isCompact = false;

    public function __construct(string | Closure | null $label = null)
    {
        $this->label = $label;
    }

    public static function make(string | Closure | null $label = null): static
    {
        return new static($label);
    }

    /**
     * @param  array<CardItem>  $items
     */
    public function schema(array $items): static
    {
        $this->schema = $items;

        return $this;
    }

    public function columns(int | Closure | null $columns): static
    {
        $this->columns = $columns;

        return $this;
    }

    public function collapsible(bool $condition = true): static
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

    public function compact(bool $condition = true): static
    {
        $this->isCompact = $condition;

        return $this;
    }

    public function isCollapsible(): bool
    {
        return $this->isCollapsible;
    }

    public function isCollapsed(): bool
    {
        return (bool) $this->evaluate($this->isCollapsed);
    }

    public function isCompact(): bool
    {
        return $this->isCompact;
    }

    public function getColumns(): ?int
    {
        return $this->evaluate($this->columns);
    }

    /**
     * @return array<CardItem>
     */
    public function getItems(): array
    {
        return collect($this->schema)
            ->filter(fn (CardItem $item): bool => $item->isVisible())
            ->sortBy(fn (CardItem $item): int => $item->getSort())
            ->values()
            ->all();
    }
}
