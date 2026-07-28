<?php

declare(strict_types=1);

namespace BokshornIt\FilamentActivityTimeline\Tests\Fixtures;

use BokshornIt\FilamentActivityTimeline\Contracts\ProvidesActivityTitle;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class TestInvoiceLine extends Model implements ProvidesActivityTitle
{
    use LogsActivity;

    protected $table = 'test_invoice_lines';

    protected $fillable = ['test_invoice_id', 'description'];

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
        return $this->description;
    }

    public function testInvoice(): BelongsTo
    {
        return $this->belongsTo(TestInvoice::class);
    }
}
