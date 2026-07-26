<?php

declare(strict_types=1);

namespace BokshornIt\FilamentActivityTimeline\Tests\Fixtures;

use BokshornIt\FilamentActivityTimeline\Contracts\ProvidesActivityTitle;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * Stands in for a portal customer: a causer that is not a user.
 */
class TestCustomer extends Model implements ProvidesActivityTitle
{
    use LogsActivity;

    protected $table = 'test_customers';

    /**
     * "notes" is intentionally left out: it is logged but not fillable, which
     * is what a restore has to cope with.
     */
    protected $fillable = ['name'];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'notes'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function activityTitle(): ?string
    {
        return $this->name;
    }
}
