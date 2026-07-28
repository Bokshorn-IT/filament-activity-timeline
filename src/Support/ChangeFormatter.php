<?php

declare(strict_types=1);

namespace BokshornIt\FilamentActivityTimeline\Support;

use BokshornIt\FilamentActivityTimeline\ActivityTimelinePlugin;
use BokshornIt\FilamentActivityTimeline\Contracts\ProvidesActivityTitle;
use Filament\Support\Contracts\HasLabel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Spatie\Activitylog\Models\Activity;
use Throwable;

/**
 * Turns a logged property bag into readable rows.
 *
 * Activity properties are stored as raw database values. Everything in here is
 * about running them back through the subject model's casts and relationships
 * so a diff reads like the record does on screen.
 */
class ChangeFormatter
{
    /**
     * Resolved related-record titles, keyed by "Class:id". A timeline mostly
     * points at the same few related records, so this saves re-querying them
     * for every row.
     *
     * @var array<string, string|null>
     */
    protected array $relatedLabels = [];

    public function __construct(
        protected readonly ActivityTimelinePlugin $plugin,
    ) {}

    public static function make(): static
    {
        return new static(ActivityTimelinePlugin::resolve());
    }

    /**
     * @return Collection<int, array{label: string, old: string, new: string, changed: bool}>
     */
    public function rows(Activity $activity): Collection
    {
        $new = ActivityChanges::attributes($activity);
        $old = ActivityChanges::old($activity);

        $subjectType = $activity->subject_type;

        return collect(array_keys($new + $old))
            ->reject(fn (string $key): bool => $this->isIgnored($key))
            ->map(function (string $key) use ($new, $old, $subjectType): array {
                $hasNew = array_key_exists($key, $new);

                $newValue = $this->formatValue($subjectType, $key, $hasNew ? $new[$key] : ($old[$key] ?? null));
                $oldValue = $this->formatValue($subjectType, $key, $old[$key] ?? null);

                return [
                    'label' => $this->fieldLabel($key),
                    'old' => $oldValue,
                    'new' => $newValue,
                    'changed' => array_key_exists($key, $old) && $hasNew && $oldValue !== $newValue,
                ];
            })
            // A value that did not change and is empty anyway says nothing.
            ->reject(fn (array $row): bool => ! $row['changed'] && $row['new'] === $this->placeholder())
            ->values();
    }

    public function isIgnored(string $key): bool
    {
        return in_array($key, $this->plugin->getIgnoredKeys(), true);
    }

    /**
     * The label for a logged column, from the host app's field label
     * namespace, falling back to a headline-cased version of the key.
     */
    public function fieldLabel(string $key): string
    {
        $translationKey = $this->plugin->getFieldLabelNamespace().'.'.$key;
        $translated = __($translationKey);

        return is_string($translated) && $translated !== $translationKey
            ? $translated
            : Str::headline($key);
    }

    public function formatValue(?string $subjectType, string $key, mixed $value): string
    {
        if (is_bool($value)) {
            return __('filament-activity-timeline::activity.boolean.'.($value ? 'true' : 'false'));
        }

        if ($value === null || $value === '') {
            return $this->placeholder();
        }

        if (is_array($value)) {
            return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: $this->placeholder();
        }

        // Morph columns hold a class name; show the model's label instead.
        if (is_string($value) && Str::endsWith($key, '_type') && class_exists($value)) {
            return SubjectResolver::make()->typeLabel($value);
        }

        $cast = $this->resolveCast($subjectType, $key);

        if ($cast !== null && enum_exists($cast)) {
            $case = $cast::tryFrom($value);

            if ($case instanceof HasLabel) {
                return (string) ($case->getLabel() ?? $value);
            }

            if ($case !== null) {
                return (string) ($case->name);
            }
        }

        if ($this->isDateCast($cast)) {
            try {
                $date = Carbon::parse($value)->setTimezone(config('app.timezone'));

                return $date->format($this->isDateTimeCast($cast)
                    ? $this->plugin->getDateTimeFormat()
                    : $this->plugin->getDateFormat());
            } catch (Throwable) {
                return (string) $value;
            }
        }

        $related = $this->resolveRelatedLabel($subjectType, $key, $value);

        if ($related !== null) {
            return $related;
        }

        return (string) $value;
    }

    /**
     * Resolve a foreign key ("customer_id" => 14) to the related record's
     * title, via the subject's BelongsTo relationship of the same name.
     */
    protected function resolveRelatedLabel(?string $subjectType, string $key, mixed $value): ?string
    {
        if (! $subjectType || ! class_exists($subjectType) || ! Str::endsWith($key, '_id')) {
            return null;
        }

        $relationName = Str::camel(Str::beforeLast($key, '_id'));

        $subject = new $subjectType;

        if (! $subject instanceof Model || ! method_exists($subject, $relationName)) {
            return null;
        }

        try {
            $relation = $subject->{$relationName}();
        } catch (Throwable) {
            return null;
        }

        if (! $relation instanceof BelongsTo || $relation instanceof MorphTo) {
            return null;
        }

        $relatedClass = $relation->getRelated()::class;
        $cacheKey = $relatedClass.':'.$value;

        if (array_key_exists($cacheKey, $this->relatedLabels)) {
            return $this->relatedLabels[$cacheKey];
        }

        $related = $relation->getRelated()->newQuery()->find($value);

        return $this->relatedLabels[$cacheKey] = $this->recordTitle($related);
    }

    /**
     * The title a model declares for itself, if it declares one at all.
     */
    public function recordTitle(?Model $record): ?string
    {
        if (! $record instanceof ProvidesActivityTitle) {
            return null;
        }

        $title = $record->activityTitle();

        return filled($title) ? (string) $title : null;
    }

    protected function resolveCast(?string $subjectType, string $key): ?string
    {
        if (! $subjectType || ! class_exists($subjectType)) {
            return null;
        }

        $subject = new $subjectType;

        if (! $subject instanceof Model) {
            return null;
        }

        $cast = $subject->getCasts()[$key] ?? null;

        return is_string($cast) ? $cast : null;
    }

    protected function isDateCast(?string $cast): bool
    {
        return $cast !== null && in_array(
            explode(':', $cast)[0],
            ['date', 'datetime', 'immutable_date', 'immutable_datetime', 'timestamp'],
            true,
        );
    }

    protected function isDateTimeCast(?string $cast): bool
    {
        return $cast !== null && in_array(
            explode(':', $cast)[0],
            ['datetime', 'immutable_datetime', 'timestamp'],
            true,
        );
    }

    protected function placeholder(): string
    {
        return $this->plugin->getPlaceholder();
    }
}
