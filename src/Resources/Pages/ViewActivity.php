<?php

declare(strict_types=1);

namespace BokshornIt\FilamentActivityTimeline\Resources\Pages;

use BokshornIt\FilamentActivityTimeline\Actions\RestoreActivityAction;
use BokshornIt\FilamentActivityTimeline\ActivityTimelinePlugin;
use BokshornIt\FilamentActivityTimeline\Support\SubjectResolver;
use Filament\Actions\Action;
use Filament\Resources\Pages\ViewRecord;
use Spatie\Activitylog\Models\Activity;

class ViewActivity extends ViewRecord
{
    public static function getResource(): string
    {
        return ActivityTimelinePlugin::resolve()->getResource();
    }

    /**
     * @return array<int, Action>
     */
    protected function getHeaderActions(): array
    {
        $subjects = SubjectResolver::make();

        return [
            Action::make('openSubject')
                ->label(__('filament-activity-timeline::activity.actions.open_subject'))
                ->icon('heroicon-m-arrow-top-right-on-square')
                ->color('gray')
                ->url(fn (Activity $record): ?string => $subjects->url($record))
                ->openUrlInNewTab()
                ->visible(fn (Activity $record): bool => $subjects->url($record) !== null),
            RestoreActivityAction::make(),
        ];
    }
}
