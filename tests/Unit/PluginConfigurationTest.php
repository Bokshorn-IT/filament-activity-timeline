<?php

declare(strict_types=1);

use BokshornIt\FilamentActivityTimeline\ActivityTimelinePlugin;
use BokshornIt\FilamentActivityTimeline\Resources\ActivityResource;
use Illuminate\Database\Eloquent\Builder;
use Spatie\Activitylog\Models\Activity;

it('resolves the plugin configured on the current panel', function (): void {
    expect(ActivityTimelinePlugin::resolve())->toBe(ActivityTimelinePlugin::get());
});

it('falls back to config defaults when nothing is configured', function (): void {
    $plugin = app(ActivityTimelinePlugin::class);

    expect($plugin->getDateFormat())->toBe('d.m.Y')
        ->and($plugin->getPlaceholder())->toBe('-')
        ->and($plugin->getTimelineLimit())->toBe(50)
        ->and($plugin->getResource())->toBe(ActivityResource::class)
        ->and($plugin->getRestorable())->toBe([]);
});

it('lets settings on the plugin win over the config file', function (): void {
    $plugin = app(ActivityTimelinePlugin::class)->dateFormat('Y-m-d');

    expect($plugin->getDateFormat())->toBe('Y-m-d');
});

it('leaves the query alone when no modifier is set', function (): void {
    $plugin = app(ActivityTimelinePlugin::class);
    $query = Activity::query();

    expect($plugin->applyQueryModifier($query))->toBe($query);
});

it('applies a query modifier to the activity query', function (): void {
    Activity::query()->create(['description' => 'kept', 'log_name' => 'keep']);
    Activity::query()->create(['description' => 'dropped', 'log_name' => 'drop']);

    $plugin = app(ActivityTimelinePlugin::class)
        ->modifyQueryUsing(fn (Builder $query): Builder => $query->where('log_name', 'keep'));

    $results = $plugin->applyQueryModifier(Activity::query())->get();

    expect($results)->toHaveCount(1)
        ->and($results->first()->description)->toBe('kept');
});
