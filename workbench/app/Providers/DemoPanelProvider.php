<?php

declare(strict_types=1);

namespace Workbench\App\Providers;

use BokshornIt\FilamentActivityTimeline\ActivityTimelinePlugin;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\View\PanelsRenderHook;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Workbench\App\Models\Article;
use Workbench\App\Models\Author;
use Workbench\App\Models\User;

/**
 * The demo panel used for the screenshots and for poking at the plugin by
 * hand. Deliberately no ->colors() call: everything renders in Filament's
 * default theme so the package is not shown through someone's brand.
 */
class DemoPanelProvider extends PanelProvider
{
    public function register(): void
    {
        parent::register();

        // The demo's own changes.php / activity_events.php / activity_subjects.php
        // live in workbench/lang, which is where the package looks them up from.
        $this->app->useLangPath(__DIR__.'/../../lang');
    }

    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('demo')
            ->path('demo')
            ->login()
            // Built by `npm run demo:css` with the Tailwind CLI straight into
            // the testbench skeleton's public directory, which avoids wiring
            // Vite into a package that ships no assets of its own.
            ->renderHook(
                PanelsRenderHook::HEAD_END,
                fn (): string => '<link rel="stylesheet" href="/demo.css" />',
            )
            ->discoverResources(in: __DIR__.'/../Filament/Resources', for: 'Workbench\\App\\Filament\\Resources')
            ->plugin(
                ActivityTimelinePlugin::make()
                    ->navigationGroup('System')
                    ->causerIcons([
                        User::class => 'heroicon-m-user',
                        Author::class => 'heroicon-m-pencil-square',
                    ])
                    ->events([
                        'published' => ['icon' => 'heroicon-m-globe-alt', 'color' => 'success'],
                        'archived' => ['icon' => 'heroicon-m-archive-box', 'color' => 'gray'],
                    ])
                    ->restorable([Article::class])
                    ->modifyQueryUsing(fn (Builder $query): Builder => $query)
            )
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ]);
    }
}
