<?php

declare(strict_types=1);

namespace BokshornIt\FilamentActivityTimeline\Tests\Fixtures;

use Spatie\Activitylog\LogOptions;

/**
 * The one LogOptions call that activitylog renamed between v4 and v5, so the
 * fixtures can stay single-sourced across both.
 */
final class TestLogOptions
{
    public static function dontLogEmpty(LogOptions $options): LogOptions
    {
        return method_exists($options, 'dontLogEmptyChanges')
            ? $options->dontLogEmptyChanges()
            : $options->dontSubmitEmptyLogs();
    }
}
