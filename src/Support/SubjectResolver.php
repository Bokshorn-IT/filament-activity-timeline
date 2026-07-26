<?php

declare(strict_types=1);

namespace BokshornIt\FilamentActivityTimeline\Support;

use BokshornIt\FilamentActivityTimeline\ActivityTimelinePlugin;
use BokshornIt\FilamentActivityTimeline\Contracts\ProvidesActivityTitle;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Spatie\Activitylog\Models\Activity;
use Throwable;

/**
 * What an activity was performed on.
 *
 * Labels come from the panel's resource for that model, which keeps them in
 * the active locale and matching the rest of the UI without a class => label
 * map in here.
 */
class SubjectResolver
{
    public function __construct(
        protected readonly ActivityTimelinePlugin $plugin,
    ) {}

    public static function make(): static
    {
        return new static(ActivityTimelinePlugin::resolve());
    }

    /**
     * "Rechnung: RE-2026-014" - the subject's type and its own title.
     */
    public function label(Activity $activity): string
    {
        if (! $activity->subject_type) {
            return $this->plugin->getPlaceholder();
        }

        $type = $this->typeLabel($activity->subject_type);
        $title = $this->title($activity->subject);

        if ($title === null) {
            return "{$type} #{$activity->subject_id}";
        }

        // Avoid doubling ("Rechnung: Rechnung #14") when a record's own title
        // already names its type.
        return str_starts_with($title, $type) ? $title : "{$type}: {$title}";
    }

    public function title(?Model $record): ?string
    {
        if (! $record instanceof ProvidesActivityTitle) {
            return null;
        }

        $title = $record->activityTitle();

        return filled($title) ? (string) $title : null;
    }

    /**
     * Prefers the model's own resource label. Models that no resource manages
     * - child records, pivots - fall back to a translation keyed by the snake
     * cased class name, then to the class name itself.
     */
    public function typeLabel(?string $type): string
    {
        if (! $type) {
            return $this->plugin->getPlaceholder();
        }

        $resource = $this->resourceFor($type);

        if ($resource !== null) {
            return $resource::getModelLabel();
        }

        $key = $this->plugin->getSubjectLabelNamespace().'.'.Str::snake(class_basename($type));
        $translated = __($key);

        return is_string($translated) && $translated !== $key
            ? $translated
            : Str::headline(class_basename($type));
    }

    /**
     * A link to the subject on its own resource - its view page, or its edit
     * page when it has no view page. Null when the record is gone or no
     * resource manages it.
     */
    public function url(Activity $activity): ?string
    {
        $resource = $this->resourceFor($activity->subject_type);

        if ($resource === null || $activity->subject === null) {
            return null;
        }

        $pages = $resource::getPages();
        $page = array_key_exists('view', $pages) ? 'view' : 'edit';

        if (! array_key_exists($page, $pages)) {
            return null;
        }

        try {
            return $resource::getUrl($page, ['record' => $activity->subject]);
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * Let the panel say which resource manages a model, rather than keeping a
     * hardcoded map here.
     *
     * @return class-string|null
     */
    public function resourceFor(?string $type): ?string
    {
        if (! $type) {
            return null;
        }

        try {
            return Filament::getModelResource($type);
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * Subject types that actually appear in the log, for the type filter.
     *
     * @return array<string, string>
     */
    public function filterOptions(): array
    {
        return Activity::query()
            ->whereNotNull('subject_type')
            ->distinct()
            ->pluck('subject_type', 'subject_type')
            ->map(fn (string $type): string => $this->typeLabel($type))
            ->all();
    }
}
