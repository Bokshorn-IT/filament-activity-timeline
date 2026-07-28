<?php

declare(strict_types=1);

/**
 * Test-only glue so the fixtures compile against either activitylog major.
 *
 * v5 moved the trait and LogOptions into sub-namespaces and left nothing
 * behind at the old paths. The plugin's own source never touches either, but
 * the fixture models do - they are ordinary host-app models, and a host app
 * writes `use LogsActivity` exactly once for the version it runs. Aliasing
 * keeps one set of fixtures instead of two.
 *
 * Loaded through composer's autoload-dev files, so the aliases exist before
 * any fixture class is autoloaded.
 */
$aliases = [
    'Spatie\Activitylog\Support\LogOptions' => 'Spatie\Activitylog\LogOptions',
    'Spatie\Activitylog\Models\Concerns\LogsActivity' => 'Spatie\Activitylog\Traits\LogsActivity',
    'Spatie\Activitylog\Models\Concerns\CausesActivity' => 'Spatie\Activitylog\Traits\CausesActivity',
];

foreach ($aliases as $v5 => $v4) {
    // Autoloading is deliberately allowed here: nothing has touched these
    // symbols yet when this file runs, so a lookup without it always says no.
    $exists = class_exists($v5) || trait_exists($v5);

    if ($exists && ! class_exists($v4, false) && ! trait_exists($v4, false)) {
        class_alias($v5, $v4);
    }
}
