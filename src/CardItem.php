<?php

namespace Harvirsidhu\FilamentCards;

use BackedEnum;
use Closure;
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

    protected ?string $page = null;

    protected string | Closure | null $url = null;

    protected bool $openUrlInNewTab = false;

    protected Alignment | string | Closure | null $alignment = null;

    public function __construct(?string $page = null, string | Closure | null $url = null)
    {
        $this->page = $page;
        $this->url = $url;
    }

    public static function make(string $pageClassOrUrl): static
    {
        if (class_exists($pageClassOrUrl) && (is_a($pageClassOrUrl, Page::class, true) || is_a($pageClassOrUrl, Resource::class, true))) {
            return new static(page: $pageClassOrUrl);
        }

        return new static(url: $pageClassOrUrl);
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

    public function openUrlInNewTab(bool $condition = true): static
    {
        $this->openUrlInNewTab = $condition;

        return $this;
    }

    public function shouldOpenUrlInNewTab(): bool
    {
        return $this->openUrlInNewTab;
    }

    public function getPage(): ?string
    {
        return $this->page;
    }

    public function getLabel(): string | Htmlable | null
    {
        $label = $this->evaluate($this->label);

        if (filled($label)) {
            return $label;
        }

        if ($this->page !== null) {
            if ($resource = $this->getPageResource()) {
                return $resource::getNavigationLabel();
            }

            return $this->page::getNavigationLabel();
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

    public function getUrl(): string
    {
        $url = $this->evaluate($this->url);

        if (filled($url)) {
            return $url;
        }

        if ($this->page !== null) {
            if (is_a($this->page, Resource::class, true)) {
                return $this->page::getUrl();
            }

            return $this->page::getUrl();
        }

        return '#';
    }

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
