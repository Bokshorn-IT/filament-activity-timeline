<?php

declare(strict_types=1);

namespace BokshornIt\FilamentActivityTimeline\Tests\Fixtures;

use BokshornIt\FilamentActivityTimeline\Contracts\ProvidesActivityTitle;
use BokshornIt\FilamentActivityTimeline\Contracts\ProvidesActivityValues;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use RuntimeException;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class TestInvoice extends Model implements ProvidesActivityTitle, ProvidesActivityValues
{
    use LogsActivity, SoftDeletes;

    protected $table = 'test_invoices';

    protected $fillable = [
        'number',
        'status',
        'test_customer_id',
        'issue_date',
        'sent_at',
        'is_paid',
        'total',
    ];

    /**
     * Mirrors the column default, so an update does not report is_paid
     * drifting from null to false on every record.
     */
    protected $attributes = [
        'is_paid' => false,
    ];

    protected $casts = [
        'status' => TestInvoiceStatus::class,
        'issue_date' => 'date',
        'sent_at' => 'datetime',
        'is_paid' => 'boolean',
    ];

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

    /**
     * The one thing the schema cannot say: a total is money, not a number.
     * The key that throws is here to prove a broken implementation cannot take
     * a timeline down with it.
     */
    public function formatActivityValue(string $key, mixed $value): ?string
    {
        return match ($key) {
            'total' => number_format((float) $value, 2, ',', '.').' EUR',
            'unrenderable' => throw new RuntimeException('Boom.'),
            default => null,
        };
    }

    public function testCustomer(): BelongsTo
    {
        return $this->belongsTo(TestCustomer::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(TestInvoiceLine::class);
    }
}
