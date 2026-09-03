<?php

namespace Harvirsidhu\FilamentCards;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class FilamentCardsServiceProvider extends PackageServiceProvider
{
    public static string $name = 'filament-cards';

    public static string $viewNamespace = 'harvirsidhu-filament-cards';

    public function configurePackage(Package $package): void
    {
        $package
            ->name(static::$name)
            ->hasViews(static::$viewNamespace)
            // The package shipped a lang directory that was never registered,
            // so every string in the view resolved against the application's
            // own translations instead — which meant the search placeholder
            // and the screen-reader labels could not be translated at all.
            ->hasTranslations();
    }
}
