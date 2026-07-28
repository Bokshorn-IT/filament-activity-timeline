<?php

declare(strict_types=1);

use BokshornIt\FilamentActivityTimeline\Support\ActivityChanges;
use Spatie\Activitylog\Models\Activity;

/**
 * The change pairs live in `properties` on activitylog v4 and in their own
 * `attribute_changes` column on v5. Both shapes are asserted here whichever
 * version happens to be installed, so neither can rot unnoticed.
 */
function activityWithRawAttributes(array $attributes): Activity
{
    $activity = new Activity;
    $activity->setRawAttributes($attributes);

    return $activity;
}

it('reads the pairs out of properties, the way v4 stores them', function () {
    $activity = activityWithRawAttributes([
        'properties' => json_encode([
            'attributes' => ['name' => 'Neu'],
            'old' => ['name' => 'Alt'],
        ]),
    ]);

    expect(ActivityChanges::attributes($activity))->toBe(['name' => 'Neu'])
        ->and(ActivityChanges::old($activity))->toBe(['name' => 'Alt'])
        ->and(ActivityChanges::isEmpty($activity))->toBeFalse();
});

it('reads the pairs out of attribute_changes, the way v5 stores them', function () {
    $activity = activityWithRawAttributes([
        'attribute_changes' => json_encode([
            'attributes' => ['name' => 'Neu'],
            'old' => ['name' => 'Alt'],
        ]),
        // v5 keeps properties for the app's own custom data.
        'properties' => json_encode(['ip' => '127.0.0.1']),
    ]);

    expect(ActivityChanges::attributes($activity))->toBe(['name' => 'Neu'])
        ->and(ActivityChanges::old($activity))->toBe(['name' => 'Alt']);
});

it('prefers the dedicated column over properties when both are present', function () {
    $activity = activityWithRawAttributes([
        'attribute_changes' => json_encode(['attributes' => ['name' => 'Aus der Spalte']]),
        'properties' => json_encode(['attributes' => ['name' => 'Aus properties']]),
    ]);

    expect(ActivityChanges::attributes($activity))->toBe(['name' => 'Aus der Spalte']);
});

it('treats an entry that logged no changes as empty', function () {
    expect(ActivityChanges::isEmpty(activityWithRawAttributes(['properties' => null])))->toBeTrue()
        ->and(ActivityChanges::isEmpty(activityWithRawAttributes([])))->toBeTrue()
        ->and(ActivityChanges::attributes(activityWithRawAttributes(['properties' => json_encode(['ip' => '::1'])])))->toBe([]);
});

it('survives a properties bag that is not a change log at all', function () {
    $activity = activityWithRawAttributes([
        'properties' => json_encode(['attributes' => 'not-an-array', 'old' => 42]),
    ]);

    expect(ActivityChanges::attributes($activity))->toBe([])
        ->and(ActivityChanges::old($activity))->toBe([]);
});
