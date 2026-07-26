<?php

declare(strict_types=1);

namespace BokshornIt\FilamentActivityTimeline;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class FilamentActivityTimelineServiceProvider extends PackageServiceProvider
{
    public static string $name = 'filament-activity-timeline';

    public static string $viewNamespace = 'filament-activity-timeline';

    public function configurePackage(Package $package): void
    {
        $package->name(static::$name)
            ->hasConfigFile()
            ->hasViews(static::$viewNamespace)
            ->hasTranslations();
    }
}
