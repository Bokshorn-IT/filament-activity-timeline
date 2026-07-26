<?php

declare(strict_types=1);

use BokshornIt\FilamentActivityTimeline\Actions\RestoreActivityAction;
use BokshornIt\FilamentActivityTimeline\ActivityTimelinePlugin;
use BokshornIt\FilamentActivityTimeline\Tests\Fixtures\TestCustomer;
use BokshornIt\FilamentActivityTimeline\Tests\Fixtures\TestInvoice;
use BokshornIt\FilamentActivityTimeline\Tests\Fixtures\TestInvoiceStatus;
use Spatie\Activitylog\Models\Activity;

it('allows restoring a model on the restorable list', function (): void {
    $customer = TestCustomer::create(['name' => 'Muster GmbH']);
    $customer->update(['name' => 'Muster AG']);

    expect(RestoreActivityAction::isRestorable(Activity::query()->latest('id')->first()))->toBeTrue();
});

it('refuses to restore a model that was never nominated', function (): void {
    $invoice = TestInvoice::create(['number' => 'RE-001']);
    $invoice->update(['number' => 'RE-002']);

    expect(RestoreActivityAction::isRestorable(Activity::query()->latest('id')->first()))->toBeFalse();
});

it('refuses to restore anything when the list is empty', function (): void {
    ActivityTimelinePlugin::get()->restorable([]);

    $customer = TestCustomer::create(['name' => 'Muster GmbH']);
    $customer->update(['name' => 'Muster AG']);

    expect(RestoreActivityAction::isRestorable(Activity::query()->latest('id')->first()))->toBeFalse();

    ActivityTimelinePlugin::get()->restorable([TestCustomer::class]);
});

it('refuses to restore a creation, which has no previous values', function (): void {
    TestCustomer::create(['name' => 'Muster GmbH']);

    expect(RestoreActivityAction::isRestorable(Activity::query()->latest('id')->first()))->toBeFalse();
});

it('writes the previous values back onto the record', function (): void {
    $customer = TestCustomer::create(['name' => 'Muster GmbH']);
    $customer->update(['name' => 'Muster AG']);

    RestoreActivityAction::restore(Activity::query()->latest('id')->first());

    expect($customer->fresh()->name)->toBe('Muster GmbH');
});

it('logs the restore as its own entry', function (): void {
    $customer = TestCustomer::create(['name' => 'Muster GmbH']);
    $customer->update(['name' => 'Muster AG']);

    $before = Activity::query()->count();

    RestoreActivityAction::restore(Activity::query()->latest('id')->first());

    expect(Activity::query()->count())->toBe($before + 1);
});

it('restores a column the model does not list as fillable', function (): void {
    $customer = TestCustomer::create(['name' => 'Muster GmbH']);
    $customer->forceFill(['notes' => 'Alte Notiz'])->save();
    $customer->forceFill(['notes' => 'Neue Notiz'])->save();

    RestoreActivityAction::restore(Activity::query()->latest('id')->first());

    expect($customer->fresh()->notes)->toBe('Alte Notiz');
});

it('says so instead of claiming success when there is nothing to restore', function (): void {
    $customer = TestCustomer::create(['name' => 'Muster GmbH']);
    $customer->update(['name' => 'Muster AG']);

    $activity = Activity::query()->latest('id')->first();

    // Put the record back by hand, so the entry's old values are already live.
    $customer->update(['name' => 'Muster GmbH']);

    RestoreActivityAction::restore($activity);

    $titles = collect(session('filament.notifications', []))->pluck('title');

    expect($titles)->toContain(__('filament-activity-timeline::activity.restore.unchanged_title'))
        ->and($titles)->not->toContain(__('filament-activity-timeline::activity.restore.restored_title'));
});

it('restores an entry taken from a collection, where strict models forbid lazy loading', function (): void {
    $first = TestCustomer::create(['name' => 'Erste GmbH']);
    $second = TestCustomer::create(['name' => 'Zweite GmbH']);

    $first->update(['name' => 'Erste AG']);
    $second->update(['name' => 'Zweite AG']);

    // A collection, not a first(): this is the shape that trips
    // Model::preventLazyLoading() when a relation is not eager loaded.
    $activity = Activity::query()->where('event', 'updated')->get()->last();

    RestoreActivityAction::restore($activity);

    expect($second->fresh()->name)->toBe('Zweite GmbH');
});

it('leaves an unrelated record untouched when restoring', function (): void {
    $invoice = TestInvoice::create(['number' => 'RE-001', 'status' => TestInvoiceStatus::Draft]);
    $customer = TestCustomer::create(['name' => 'Muster GmbH']);
    $customer->update(['name' => 'Muster AG']);

    RestoreActivityAction::restore(Activity::query()->latest('id')->first());

    expect($invoice->fresh()->number)->toBe('RE-001');
});
