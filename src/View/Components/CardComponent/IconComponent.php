<?php

namespace Harvirsidhu\FilamentCards\View\Components\CardComponent;

use Filament\Support\View\Components\Contracts\HasColor;
use Harvirsidhu\FilamentCards\View\Components\CardComponent;

/**
 * The colour treatment for a card's icon.
 *
 * @see CardComponent
 */
class IconComponent implements HasColor
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
