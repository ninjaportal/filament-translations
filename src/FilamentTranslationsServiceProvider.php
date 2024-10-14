<?php

namespace NinjaPortal\FilamentTranslations;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class FilamentTranslationsServiceProvider extends PackageServiceProvider
{

    public function configurePackage(Package $package): void
    {
        $package
            ->name('filament-translations')
            ->hasTranslations();
    }

}
