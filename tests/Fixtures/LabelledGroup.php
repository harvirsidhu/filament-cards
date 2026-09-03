<?php

namespace Harvirsidhu\FilamentCards\Tests\Fixtures;

use Filament\Support\Contracts\HasLabel;

enum LabelledGroup: string implements HasLabel
{
    case Finance = 'fin';

    public function getLabel(): string
    {
        return 'Finance & Billing';
    }
}
