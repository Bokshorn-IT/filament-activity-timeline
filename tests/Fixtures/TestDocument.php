<?php

declare(strict_types=1);

namespace BokshornIt\FilamentActivityTimeline\Tests\Fixtures;

use BokshornIt\FilamentActivityTimeline\Contracts\ProvidesActivitySubjectLabel;
use BokshornIt\FilamentActivityTimeline\Contracts\ProvidesActivityTitle;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class TestDocument extends Model implements ProvidesActivitySubjectLabel, ProvidesActivityTitle
{
    use LogsActivity;

    protected $table = 'test_documents';

    protected $fillable = ['number', 'status'];

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
        return $this->number;
    }

    public function activitySubjectLabel(): ?string
    {
        return $this->status === 'signed' ? null : 'Draft';
    }
}
