<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Activitylog\Models\Concerns\LogsActivity;

/**
 * spatie/laravel-activitylog ships its migrations as .stub files, which
 * loadMigrationsFrom() cannot pick up. This mirrors the published result for
 * whichever major is installed: v5 keeps the before/after values in their own
 * attribute_changes column and dropped batches, v4 keeps them in properties.
 * The table name and connection are config in v4 and fixed in v5.
 */
return new class extends Migration
{
    public function up(): void
    {
        $isV5 = trait_exists(LogsActivity::class);

        Schema::connection($isV5 ? null : config('activitylog.database_connection'))
            ->create($isV5 ? 'activity_log' : config('activitylog.table_name'), function (Blueprint $table) use ($isV5): void {
                $table->bigIncrements('id');
                $table->string('log_name')->nullable();
                $table->text('description');
                $table->nullableMorphs('subject', 'subject');
                $table->string('event')->nullable();
                $table->nullableMorphs('causer', 'causer');

                if ($isV5) {
                    $table->json('attribute_changes')->nullable();
                }

                $table->json('properties')->nullable();

                if (! $isV5) {
                    $table->uuid('batch_uuid')->nullable();
                }

                $table->timestamps();
                $table->index('log_name');
            });
    }

    public function down(): void
    {
        $isV5 = trait_exists(LogsActivity::class);

        Schema::connection($isV5 ? null : config('activitylog.database_connection'))
            ->dropIfExists($isV5 ? 'activity_log' : config('activitylog.table_name'));
    }
};
