<?php

declare(strict_types=1);

namespace Workbench\App\Models;

use BokshornIt\FilamentActivityTimeline\Contracts\ProvidesActivityTitle;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * A child record with no Filament resource of its own, so the demo shows both
 * withRelations() and the activity_subjects label fallback.
 */
class Revision extends Model implements ProvidesActivityTitle
{
    use LogsActivity;

    protected $fillable = ['article_id', 'summary', 'word_count'];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function activityTitle(): ?string
    {
        return $this->summary;
    }

    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class);
    }
}
