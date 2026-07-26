<?php

declare(strict_types=1);

namespace BokshornIt\FilamentActivityTimeline\Resources\Pages;

use BokshornIt\FilamentActivityTimeline\ActivityTimelinePlugin;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Url;

class ListActivities extends ListRecords
{
    /**
     * Set by the "show all" link in a record's timeline.
     */
    #[Url(as: 'subject_type')]
    public ?string $subjectType = null;

    #[Url(as: 'subject_id')]
    public ?string $subjectId = null;

    public static function getResource(): string
    {
        return ActivityTimelinePlugin::resolve()->getResource();
    }

    /**
     * @return array<int, Action>
     */
    protected function getHeaderActions(): array
    {
        return [];
    }

    protected function getTableQuery(): ?Builder
    {
        $query = parent::getTableQuery();

        if ($query === null || blank($this->subjectType)) {
            return $query;
        }

        return $query
            ->where('subject_type', $this->subjectType)
            ->when(filled($this->subjectId), fn (Builder $query): Builder => $query->where('subject_id', $this->subjectId));
    }
}
