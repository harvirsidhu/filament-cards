<?php

namespace Harvirsidhu\FilamentCards;

use Closure;
use Filament\Contracts\Plugin;
use Filament\Facades\Filament;
use Filament\Panel;
use Filament\Support\Concerns\EvaluatesClosures;
use Filament\Support\Enums\Alignment;
use Filament\Support\Enums\IconPosition;
use Filament\Support\Enums\IconSize;

/**
 * Panel-wide defaults for every cards page in the panel.
 *
 * Without this, each page had to redeclare the same four or five static
 * properties to get a consistent look. Values set here are the middle tier of
 * the resolution order used by CardsPage:
 *
 *     page static property  >  plugin default  >  package default
 *
 * so a page that declares `protected static int|string|array $columns = 4`
 * still wins, and pages that declare nothing follow the panel.
 */
class FilamentCardsPlugin implements Plugin
{
    use EvaluatesClosures;

    /** @var int|string|array<string, int|string|null>|Closure|null */
    protected int | string | array | Closure | null $columns = null;

    protected Alignment | string | Closure | null $itemsAlignment = null;

    protected IconSize | string | Closure | null $iconSize = null;

    protected IconPosition | string | Closure | null $iconPosition = null;

    protected bool | Closure | null $isIconInlined = null;

    protected bool | Closure | null $isSearchable = null;

    protected string | Closure | null $searchPlaceholder = null;

    protected bool | Closure | null $shouldPersistCollapsed = null;

    public function getId(): string
    {
        return 'filament-cards';
    }

    public function register(Panel $panel): void
    {
        //
    }

    public function boot(Panel $panel): void
    {
        //
    }

    public static function make(): static
    {
        return app(static::class);
    }

    public static function get(): static
    {
        /** @var static $plugin */
        $plugin = filament(app(static::class)->getId());

        return $plugin;
    }

    /**
     * The plugin as registered on the current panel, or null when the panel
     * does not use it. Callers fall back to their own defaults, so a cards
     * page works fine in a panel that never registered the plugin.
     */
    public static function getCurrent(): ?static
    {
        // The same lookup Filament's own getPlugin() performs, minus the
        // exception when the plugin is absent.
        $panel = Filament::getCurrentOrDefaultPanel();

        if ($panel === null) {
            return null;
        }

        $id = app(static::class)->getId();

        if (! $panel->hasPlugin($id)) {
            return null;
        }

        /** @var static $plugin */
        $plugin = $panel->getPlugin($id);

        return $plugin;
    }

    /**
     * @param  int|string|array<string, int|string|null>|Closure|null  $columns
     */
    public function columns(int | string | array | Closure | null $columns): static
    {
        $this->columns = $columns;

        return $this;
    }

    /**
     * @return int|string|array<string, int|string|null>|null
     */
    public function getColumns(): int | string | array | null
    {
        return $this->evaluate($this->columns);
    }

    public function itemsAlignment(Alignment | string | Closure | null $alignment): static
    {
        $this->itemsAlignment = $alignment;

        return $this;
    }

    public function getItemsAlignment(): Alignment | string | null
    {
        return $this->evaluate($this->itemsAlignment);
    }

    public function iconSize(IconSize | string | Closure | null $size): static
    {
        $this->iconSize = $size;

        return $this;
    }

    public function getIconSize(): IconSize | string | null
    {
        return $this->evaluate($this->iconSize);
    }

    public function iconPosition(IconPosition | string | Closure | null $position): static
    {
        $this->iconPosition = $position;

        return $this;
    }

    public function getIconPosition(): IconPosition | string | null
    {
        return $this->evaluate($this->iconPosition);
    }

    public function iconInlined(bool | Closure | null $condition = true): static
    {
        $this->isIconInlined = $condition;

        return $this;
    }

    public function isIconInlined(): ?bool
    {
        $value = $this->evaluate($this->isIconInlined);

        return $value === null ? null : (bool) $value;
    }

    public function searchable(bool | Closure | null $condition = true): static
    {
        $this->isSearchable = $condition;

        return $this;
    }

    public function isSearchable(): ?bool
    {
        $value = $this->evaluate($this->isSearchable);

        return $value === null ? null : (bool) $value;
    }

    public function searchPlaceholder(string | Closure | null $placeholder): static
    {
        $this->searchPlaceholder = $placeholder;

        return $this;
    }

    public function getSearchPlaceholder(): ?string
    {
        return $this->evaluate($this->searchPlaceholder);
    }

    public function persistCollapsed(bool | Closure | null $condition = true): static
    {
        $this->shouldPersistCollapsed = $condition;

        return $this;
    }

    public function shouldPersistCollapsed(): ?bool
    {
        $value = $this->evaluate($this->shouldPersistCollapsed);

        return $value === null ? null : (bool) $value;
    }
}
