<?php

declare(strict_types=1);

namespace BokshornIt\FilamentActivityTimeline\Actions;

use BokshornIt\FilamentActivityTimeline\ActivityTimelinePlugin;
use BokshornIt\FilamentActivityTimeline\Support\ActivityChanges;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Spatie\Activitylog\Models\Activity;

/**
 * Writes an entry's logged "old" values back onto its subject.
 *
 * Stays hidden unless the subject's model is listed on ->restorable(). The
 * restore gets logged itself.
 */
class RestoreActivityAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'restoreActivity';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label(__('filament-activity-timeline::activity.restore.label'));
        $this->icon('heroicon-m-arrow-uturn-left');
        $this->color('warning');
        $this->requiresConfirmation();
        $this->modalHeading(__('filament-activity-timeline::activity.restore.heading'));
        $this->modalDescription(__('filament-activity-timeline::activity.restore.description'));
        $this->modalSubmitActionLabel(__('filament-activity-timeline::activity.restore.submit'));

        $this->visible(fn (Activity $record): bool => static::isRestorable($record)
            && (auth()->user()?->can('restore', $record) ?? false));

        $this->action(fn (Activity $record) => static::restore($record));
    }

    public static function isRestorable(Activity $activity): bool
    {
        // Both of these are public entry points, so the entry can arrive from
        // anywhere. Reading ->subject on one that came out of a collection
        // without an eager load is a lazy load, which an application running
        // Model::preventLazyLoading() turns into an exception. loadMissing is
        // a no-op when the resource already eager loaded it.
        $activity->loadMissing('subject');

        if ($activity->event !== 'updated' || $activity->subject === null) {
            return false;
        }

        if (blank(ActivityChanges::old($activity))) {
            return false;
        }

        $restorable = ActivityTimelinePlugin::resolve()->getRestorable();

        foreach ($restorable as $model) {
            if ($activity->subject instanceof $model) {
                return true;
            }
        }

        return false;
    }

    public static function restore(Activity $activity): void
    {
        $activity->loadMissing('subject');

        $subject = $activity->subject;
        $old = ActivityChanges::old($activity);

        if ($subject === null || blank($old) || ! static::isRestorable($activity)) {
            Notification::make()
                ->warning()
                ->title(__('filament-activity-timeline::activity.restore.failed_title'))
                ->body(__('filament-activity-timeline::activity.restore.failed_body'))
                ->send();

            return;
        }

        // Columns the model has since dropped would make the write fail.
        $old = array_intersect_key($old, $subject->getAttributes());

        // forceFill, not fill: these values came out of the record itself, and
        // on a model using $fillable a plain fill() would quietly skip the very
        // columns being restored and still report success.
        $subject->forceFill($old);

        if (! $subject->isDirty()) {
            Notification::make()
                ->warning()
                ->title(__('filament-activity-timeline::activity.restore.unchanged_title'))
                ->body(__('filament-activity-timeline::activity.restore.unchanged_body'))
                ->send();

            return;
        }

        $subject->save();

        Notification::make()
            ->success()
            ->title(__('filament-activity-timeline::activity.restore.restored_title'))
            ->body(__('filament-activity-timeline::activity.restore.restored_body'))
            ->send();
    }
}
