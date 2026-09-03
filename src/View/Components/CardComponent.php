<?php

namespace Harvirsidhu\FilamentCards\View\Components;

use Filament\Support\View\Components\Contracts\HasColor;

/**
 * The colour treatment for a card's accent border.
 *
 * Filament resolves this through `$attributes->color()`, which emits
 * `fi-color fi-color-{name}` — and those classes define `--color-50` through
 * `--color-950` for *every* colour registered on the panel, including custom
 * ones. That is why the card stylesheet can say `border-s-color-500` and have
 * it work for `->color('brand')` without this package knowing `brand` exists.
 *
 * An empty map is deliberate: the card only needs the custom properties, not
 * the generated text/background utility classes that a badge or button wants.
 */
class CardComponent implements HasColor
{
    /**
     * @param  array<int, string>  $color
     * @return array<string, int>
     */
    public function getColorMap(array $color): array
    {
        return [];
    }
}
