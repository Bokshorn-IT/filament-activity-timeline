<?php

declare(strict_types=1);

namespace BokshornIt\FilamentActivityTimeline\Support;

use BokshornIt\FilamentActivityTimeline\ActivityTimelinePlugin;
use BokshornIt\FilamentActivityTimeline\Contracts\ProvidesActivityTitle;
use BokshornIt\FilamentActivityTimeline\Contracts\ProvidesActivityValues;
use Filament\Support\Contracts\HasLabel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Eloquent\SoftDeletes;
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

    /**
     * A blank instance per subject class, since casts, relationships and the
     * value hook are all read off the class rather than off a record.
     *
     * @var array<class-string, Model|null>
     */
    protected array $subjects = [];

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

                // Each side is formatted against its own values, so a morph
                // column reads the type it had at that point rather than the
                // one it ended up with.
                $newValue = $this->formatValue($subjectType, $key, $hasNew ? $new[$key] : ($old[$key] ?? null), $hasNew ? $new : $old);
                $oldValue = $this->formatValue($subjectType, $key, $old[$key] ?? null, $old);

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

    /**
     * @param  array<string, mixed>  $context  The other values logged on the
     *                                         same side of the change, which
     *                                         is where a morph column finds
     *                                         the type belonging to its id.
     */
    public function formatValue(?string $subjectType, string $key, mixed $value, array $context = []): string
    {
        if (is_bool($value)) {
            return __('filament-activity-timeline::activity.boolean.'.($value ? 'true' : 'false'));
        }

        if ($value === null || $value === '') {
            return $this->placeholder();
        }

        // What a column means is the model's to say, and it says so before
        // anything the schema could tell us: money, quantities, a custom cast.
        $own = $this->modelValue($subjectType, $key, $value);

        if ($own !== null) {
            return $own;
        }

        if (is_array($value)) {
            return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: $this->placeholder();
        }

        // Morph columns hold a class name, or the alias a morph map gave it;
        // show the model's label instead.
        if (is_string($value) && Str::endsWith($key, '_type')) {
            $class = $this->morphedClass($value);

            if ($class !== null) {
                return SubjectResolver::make()->typeLabel($class);
            }
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

        $related = $this->resolveRelatedLabel($subjectType, $key, $value, $context);

        if ($related !== null) {
            return $related;
        }

        return (string) $value;
    }

    /**
     * The model's own rendering of a value, if it offers one.
     */
    protected function modelValue(?string $subjectType, string $key, mixed $value): ?string
    {
        $subject = $this->subject($subjectType);

        if (! $subject instanceof ProvidesActivityValues) {
            return null;
        }

        try {
            $formatted = $subject->formatActivityValue($key, $value);
        } catch (Throwable) {
            return null;
        }

        return $formatted === null || $formatted === '' ? null : $formatted;
    }

    /**
     * Resolve a foreign key ("customer_id" => 14) to the related record's
     * title, via the subject's BelongsTo relationship of the same name.
     *
     * @param  array<string, mixed>  $context
     */
    protected function resolveRelatedLabel(?string $subjectType, string $key, mixed $value, array $context = []): ?string
    {
        if (! Str::endsWith($key, '_id')) {
            return null;
        }

        $relationName = Str::camel(Str::beforeLast($key, '_id'));

        $subject = $this->subject($subjectType);

        if (! $subject instanceof Model || ! method_exists($subject, $relationName)) {
            return null;
        }

        try {
            $relation = $subject->{$relationName}();
        } catch (Throwable) {
            return null;
        }

        if (! $relation instanceof BelongsTo) {
            return null;
        }

        $relatedClass = $relation instanceof MorphTo
            ? $this->morphedClass($context[$relation->getMorphType()] ?? null)
            : $relation->getRelated()::class;

        if ($relatedClass === null) {
            return null;
        }

        $cacheKey = $relatedClass.':'.$value;

        if (array_key_exists($cacheKey, $this->relatedLabels)) {
            return $this->relatedLabels[$cacheKey];
        }

        return $this->relatedLabels[$cacheKey] = $this->recordTitle($this->findRelated($relatedClass, $value));
    }

    /**
     * The record a foreign key points at, deleted ones included: history keeps
     * referring to records that have since gone, and their name is the whole
     * point of resolving the key.
     *
     * @param  class-string<Model>  $class
     */
    protected function findRelated(string $class, mixed $value): ?Model
    {
        $query = (new $class)->newQuery();

        if (in_array(SoftDeletes::class, class_uses_recursive($class), true)) {
            $query->withTrashed();
        }

        return $query->find($value);
    }

    /**
     * The model behind a morph type: the class name itself, or whatever a
     * morph map aliased it to. Null when the type is missing from the change
     * or names something that is no longer a model.
     *
     * @return class-string<Model>|null
     */
    protected function morphedClass(mixed $type): ?string
    {
        if (! is_string($type) || $type === '') {
            return null;
        }

        $class = Relation::getMorphedModel($type) ?? $type;

        return is_string($class) && class_exists($class) && is_a($class, Model::class, true)
            ? $class
            : null;
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
        $cast = $this->subject($subjectType)?->getCasts()[$key] ?? null;

        return is_string($cast) ? $cast : null;
    }

    /**
     * A blank instance of the subject class. An entry whose subject class was
     * renamed or removed since it was logged still has to render, so a class
     * that will not instantiate resolves to null rather than throwing.
     */
    protected function subject(?string $subjectType): ?Model
    {
        if (! $subjectType || ! class_exists($subjectType)) {
            return null;
        }

        if (array_key_exists($subjectType, $this->subjects)) {
            return $this->subjects[$subjectType];
        }

        try {
            $subject = new $subjectType;
        } catch (Throwable) {
            $subject = null;
        }

        return $this->subjects[$subjectType] = $subject instanceof Model ? $subject : null;
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
