<?php

declare(strict_types=1);

namespace BokshornIt\FilamentActivityTimeline\Tests\Fixtures;

use BokshornIt\FilamentActivityTimeline\Contracts\ProvidesActivityTitle;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * A record that hangs off whichever model it was written about, so the diff
 * has a morph pair to resolve.
 */
class TestNote extends Model implements ProvidesActivityTitle
{
    use LogsActivity;

    protected $table = 'test_notes';

    protected $fillable = ['notable_type', 'notable_id', 'body'];

    public function getActivitylogOptions(): LogOptions
    {
        return TestLogOptions::dontLogEmpty(
            LogOptions::defaults()
                ->logFillable()
                ->logOnlyDirty(),
        );
    }

    public function activityTitle(): ?string
    {
        return $this->body;
    }

    public function notable(): MorphTo
    {
        return $this->morphTo();
    }
}
