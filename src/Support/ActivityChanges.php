<?php

declare(strict_types=1);

namespace BokshornIt\FilamentActivityTimeline\Support;

use Illuminate\Contracts\Support\Arrayable;
use Spatie\Activitylog\Models\Activity;

/**
 * Where an entry keeps its before/after values.
 *
 * activitylog v4 stores them inside `properties` alongside whatever the app
 * logged itself; v5 moved them into their own `attribute_changes` column and
 * left `properties` for custom data only. The inner shape is identical in
 * both, so reading through here is all it takes to support either.
 */
final class ActivityChanges
{
    /**
     * The values as they are now.
     *
     * @return array<string, mixed>
     */
    public static function attributes(Activity $activity): array
    {
        return self::side($activity, 'attributes');
    }

    /**
     * The values as they were before the change.
     *
     * @return array<string, mixed>
     */
    public static function old(Activity $activity): array
    {
        return self::side($activity, 'old');
    }

    /**
     * Whether this entry recorded any before/after values at all.
     */
    public static function isEmpty(Activity $activity): bool
    {
        return self::attributes($activity) === [] && self::old($activity) === [];
    }

    /**
     * @return array<string, mixed>
     */
    private static function side(Activity $activity, string $key): array
    {
        $values = self::normalize(self::bag($activity))[$key] ?? [];

        return is_array($values) ? $values : [];
    }

    /**
     * A cast Activity model hands over a Collection, but the activity model is
     * a documented extension point - one that forgets the cast hands over the
     * raw JSON instead.
     *
     * @return array<string, mixed>
     */
    private static function normalize(mixed $bag): array
    {
        return match (true) {
            $bag instanceof Arrayable => $bag->toArray(),
            is_array($bag) => $bag,
            is_string($bag) => is_array($decoded = json_decode($bag, true)) ? $decoded : [],
            default => [],
        };
    }

    /**
     * The column holding the change pairs, whichever version is installed.
     *
     * getAttributes() rather than a property read: on v4 there is no
     * `attribute_changes` attribute at all, and an app running
     * Model::preventAccessingMissingAttributes() turns reading one into an
     * exception rather than null.
     */
    private static function bag(Activity $activity): mixed
    {
        return array_key_exists('attribute_changes', $activity->getAttributes())
            ? $activity->attribute_changes
            : $activity->properties;
    }
}
