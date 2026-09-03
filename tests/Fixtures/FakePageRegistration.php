<?php

namespace Harvirsidhu\FilamentCards\Tests\Fixtures;

/**
 * Stands in for Filament\Resources\Pages\PageRegistration, which is final and
 * needs routing state the package's discovery never touches.
 */
class FakePageRegistration
{
    public function __construct(protected string $page) {}

    public function getPage(): string
    {
        return $this->page;
    }
}
