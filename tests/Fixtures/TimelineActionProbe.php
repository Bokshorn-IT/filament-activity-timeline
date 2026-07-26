<?php

declare(strict_types=1);

namespace BokshornIt\FilamentActivityTimeline\Tests\Fixtures;

use BokshornIt\FilamentActivityTimeline\Actions\ActivityTimelineAction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Spatie\Activitylog\Models\Activity;

/**
 * Exposes the action's query building so it can be tested without mounting a
 * Livewire component around it.
 */
class TimelineActionProbe extends ActivityTimelineAction
{
    /**
     * @return Collection<int, Activity>
     */
    public function activitiesFor(Model $record): Collection
    {
        return $this->getActivities($record);
    }

    public function countFor(Model $record): int
    {
        return $this->countActivities($record);
    }

    /**
     * @return Collection<int, Model>
     */
    public function subjectsFor(Model $record): Collection
    {
        return $this->getSubjects($record);
    }
}
