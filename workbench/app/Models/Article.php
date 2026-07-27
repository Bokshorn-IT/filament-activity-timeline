<?php

declare(strict_types=1);

namespace Workbench\App\Models;

use BokshornIt\FilamentActivityTimeline\Contracts\ProvidesActivityTitle;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Article extends Model implements ProvidesActivityTitle
{
    use LogsActivity;

    protected $fillable = [
        'title',
        'status',
        'author_id',
        'due_date',
        'published_at',
        'is_featured',
        'reading_minutes',
    ];

    protected $casts = [
        'status' => ArticleStatus::class,
        'due_date' => 'date',
        'published_at' => 'datetime',
        'is_featured' => 'boolean',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function activityTitle(): ?string
    {
        return $this->title;
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(Author::class);
    }

    public function revisions(): HasMany
    {
        return $this->hasMany(Revision::class);
    }
}
