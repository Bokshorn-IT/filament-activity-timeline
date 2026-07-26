<?php

declare(strict_types=1);

namespace BokshornIt\FilamentActivityTimeline\Actions;

use BokshornIt\FilamentActivityTimeline\ActivityTimelinePlugin;
use Closure;
use Filament\Actions\Action;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Spatie\Activitylog\Models\Activity;
use Throwable;

/**
 * A slide-over showing everything that happened to a record, newest first.
 */
class ActivityTimelineAction extends Action
{
    /** @var array<int, string>|Closure */
    protected array|Closure $timelineRelations = [];

    protected int|Closure|null $limit = null;

    public static function getDefaultName(): ?string
    {
        return 'activityTimeline';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label(__('filament-activity-timeline::activity.action.label'));
        $this->modalHeading(__('filament-activity-timeline::activity.action.heading'));
        $this->icon('heroicon-o-archive-box');
        $this->modalIcon('heroicon-o-archive-box');
        $this->color('gray');
        $this->modalSubmitAction(false);
        $this->modalCancelActionLabel(__('filament-activity-timeline::activity.action.close'));
        $this->slideOver();

        $this->modalContent(function (Model $record): View {
            $activities = $this->getActivities($record);

            return view('filament-activity-timeline::timeline', [
                'activities' => $activities,
                'total' => $this->countActivities($record),
                'shown' => $activities->count(),
                'showAllUrl' => $this->getShowAllUrl($record),
            ]);
        });
    }

    /**
     * Pull related records into the same timeline, so an invoice can show what
     * happened to its lines and payments as well.
     *
     * @param  array<int, string>|Closure  $relations
     */
    public function withRelations(array|Closure $relations): static
    {
        $this->timelineRelations = $relations;

        return $this;
    }

    /**
     * @return array<int, string>
     */
    public function getRelations(): array
    {
        return $this->evaluate($this->timelineRelations) ?? [];
    }

    public function limit(int|Closure|null $limit): static
    {
        $this->limit = $limit;

        return $this;
    }

    public function getLimit(): ?int
    {
        return $this->evaluate($this->limit)
            ?? ActivityTimelinePlugin::resolve()->getTimelineLimit();
    }

    /**
     * Link to the full history on the activity resource, used when the
     * timeline had to cut entries off.
     */
    protected function getShowAllUrl(Model $record): ?string
    {
        $resource = ActivityTimelinePlugin::resolve()->getResource();

        if (! $resource::canViewAny()) {
            return null;
        }

        try {
            return $resource::getUrl('index', [
                'subject_type' => $record->getMorphClass(),
                'subject_id' => $record->getKey(),
            ]);
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * @return Collection<int, Activity>
     */
    protected function getActivities(Model $record): Collection
    {
        // Several changes to one record commonly land in the same second, so
        // created_at alone leaves their order to the database.
        $query = $this->buildQuery($record)
            ->with(['causer', 'subject'])
            ->orderByDesc('created_at')
            ->orderByDesc('id');

        if ($limit = $this->getLimit()) {
            $query->limit($limit);
        }

        return $query->get();
    }

    protected function countActivities(Model $record): int
    {
        return $this->buildQuery($record)->count();
    }

    /**
     * @return Builder<Activity>
     */
    protected function buildQuery(Model $record): Builder
    {
        $subjects = $this->getSubjects($record);

        $query = Activity::query()
            ->where(function ($query) use ($subjects): void {
                foreach ($subjects as $subject) {
                    $query->orWhere(function ($subQuery) use ($subject): void {
                        $subQuery->where('subject_type', $subject->getMorphClass())
                            ->where('subject_id', $subject->getKey());
                    });
                }
            });

        return ActivityTimelinePlugin::resolve()->applyQueryModifier($query);
    }

    /**
     * The record plus whatever ->withRelations() asked for.
     *
     * @return Collection<int, Model>
     */
    protected function getSubjects(Model $record): Collection
    {
        $subjects = collect([$record]);

        foreach ($this->getRelations() as $relation) {
            try {
                $related = $record->{$relation};
            } catch (Throwable) {
                // A renamed or removed relation should not break the timeline.
                continue;
            }

            if ($related === null) {
                continue;
            }

            $subjects = $subjects->merge(
                $related instanceof EloquentCollection ? $related : [$related]
            );
        }

        return $subjects->filter(fn (Model $subject): bool => $subject->exists)->values();
    }
}
