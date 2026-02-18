<?php

namespace Harvirsidhu\FilamentCards;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class FilamentCardsServiceProvider extends PackageServiceProvider
{
    public static string $name = 'filament-cards';

    public static string $viewNamespace = 'filament-cards';

    public function configurePackage(Package $package): void
    {
        $package
            ->name(static::$name)
            ->hasViews(static::$viewNamespace);
    }
}
