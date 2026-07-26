<?php

declare(strict_types=1);

namespace BokshornIt\FilamentActivityTimeline\Tests;

use BladeUI\Heroicons\BladeHeroiconsServiceProvider;
use BladeUI\Icons\BladeIconsServiceProvider;
use BokshornIt\FilamentActivityTimeline\FilamentActivityTimelineServiceProvider;
use BokshornIt\FilamentActivityTimeline\Tests\Fixtures\TestPanelProvider;
use Filament\Actions\ActionsServiceProvider;
use Filament\Facades\Filament;
use Filament\FilamentServiceProvider;
use Filament\Forms\FormsServiceProvider;
use Filament\Infolists\InfolistsServiceProvider;
use Filament\Notifications\NotificationsServiceProvider;
use Filament\Schemas\SchemasServiceProvider;
use Filament\Support\SupportServiceProvider;
use Filament\Tables\TablesServiceProvider;
use Filament\Widgets\WidgetsServiceProvider;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\LivewireServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;
use Spatie\Activitylog\ActivitylogServiceProvider;

abstract class TestCase extends Orchestra
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Without a current panel, the plugin cannot be resolved from it and
        // every lookup would silently fall back to config defaults.
        Filament::setCurrentPanel(Filament::getPanel('testing'));

        // Host apps commonly run strict models. Selecting a subset of columns
        // and then eager loading something that needs the ones left out only
        // blows up under this, so the package is tested with it on.
        Model::shouldBeStrict();
    }

    /**
     * Filament\Support has to come before Livewire here. It rebinds Livewire's
     * DataStore to its own subclass with bind(), and bind() drops whatever
     * shared instance was registered for that key. Put it after Livewire and
     * app(DataStore::class) returns a new object every time, so Livewire writes
     * its state to one instance and reads from another. Every component render
     * then blows up on a null error bag.
     */
    protected function getPackageProviders($app): array
    {
        return [
            SupportServiceProvider::class,
            LivewireServiceProvider::class,
            ActionsServiceProvider::class,
            ActivitylogServiceProvider::class,
            BladeHeroiconsServiceProvider::class,
            BladeIconsServiceProvider::class,
            FilamentServiceProvider::class,
            FormsServiceProvider::class,
            InfolistsServiceProvider::class,
            NotificationsServiceProvider::class,
            SchemasServiceProvider::class,
            TablesServiceProvider::class,
            WidgetsServiceProvider::class,
            FilamentActivityTimelineServiceProvider::class,
            TestPanelProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        // Defaults to sqlite in memory. CI also runs the suite against MySQL
        // and Postgres by setting DB_CONNECTION, since a host application can
        // be on any of the three.
        $connection = env('DB_CONNECTION', 'sqlite');

        $app['config']->set('database.default', $connection);

        if ($connection === 'sqlite') {
            // Keep Testbench's own sqlite connection (Laravel 13 expects keys
            // like transaction_mode and pragmas on it) and only move it into
            // memory.
            $app['config']->set('database.connections.sqlite.database', ':memory:');
            $app['config']->set('database.connections.sqlite.foreign_key_constraints', false);
        }

        $app['config']->set('app.key', 'base64:'.base64_encode(random_bytes(32)));
        $app['config']->set('app.timezone', 'Europe/Berlin');
    }

    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/database/migrations');
    }
}
