<?php

declare(strict_types=1);

namespace BokshornIt\FilamentActivityTimeline\Tests\Fixtures;

use BokshornIt\FilamentActivityTimeline\ActivityTimelinePlugin;
use Filament\Panel;
use Filament\PanelProvider;

class TestPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('testing')
            ->path('testing')
            ->plugin(
                ActivityTimelinePlugin::make()
                    ->causerIcons([
                        TestCustomer::class => 'heroicon-m-building-office',
                        TestUser::class => 'heroicon-m-user',
                    ])
                    ->events([
                        'invoice_sent' => ['icon' => 'heroicon-m-paper-airplane', 'color' => 'info'],
                    ])
                    ->restorable([
                        TestCustomer::class,
                    ])
            );
    }
}
