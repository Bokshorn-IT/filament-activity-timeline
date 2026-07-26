<?php

declare(strict_types=1);

use BokshornIt\FilamentActivityTimeline\ActivityTimelinePlugin;
use BokshornIt\FilamentActivityTimeline\Resources\ActivityResource;
use BokshornIt\FilamentActivityTimeline\Resources\Pages\ListActivities;
use BokshornIt\FilamentActivityTimeline\Resources\Pages\ViewActivity;
use BokshornIt\FilamentActivityTimeline\Tests\Fixtures\TestCustomer;
use BokshornIt\FilamentActivityTimeline\Tests\Fixtures\TestInvoice;
use BokshornIt\FilamentActivityTimeline\Tests\Fixtures\TestInvoiceStatus;
use BokshornIt\FilamentActivityTimeline\Tests\Fixtures\TestUser;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Spatie\Activitylog\Models\Activity;

beforeEach(function (): void {
    // refresh() so every column is loaded: logging out reads remember_token,
    // which a freshly created instance does not carry.
    $this->user = TestUser::create(['name' => 'Johannes', 'email' => 'j@example.test'])->refresh();
    $this->actingAs($this->user);

    Gate::before(fn () => true);
});

it('renders the list page', function (): void {
    TestInvoice::create(['number' => 'RE-001']);

    Livewire::test(ListActivities::class)
        ->assertSuccessful()
        ->assertSee('Created')
        ->assertSee('RE-001');
});

it('shows the causer name in the table', function (): void {
    $invoice = TestInvoice::create(['number' => 'RE-001']);

    activity()->performedOn($invoice)->by($this->user)->event('updated')->log('changed');

    Livewire::test(ListActivities::class)
        ->assertSuccessful()
        ->assertSee('Johannes');
});

it('shows System for an activity with no causer', function (): void {
    // spatie attaches the authenticated user as causer automatically, so an
    // unattended write - a queued job, the scheduler, an inbound webhook -
    // means logging with nobody signed in.
    auth()->logout();

    TestInvoice::create(['number' => 'RE-001']);

    $this->actingAs($this->user);

    Livewire::test(ListActivities::class)
        ->assertSuccessful()
        ->assertSee('System');
});

it('renders a portal customer as the causer', function (): void {
    $customer = TestCustomer::create(['name' => 'Muster GmbH']);
    $invoice = TestInvoice::create(['number' => 'RE-001']);

    activity()->performedOn($invoice)->by($customer)->event('updated')->log('changed');

    Livewire::test(ListActivities::class)
        ->assertSuccessful()
        ->assertSee('Muster GmbH');
});

it('renders the view page with its diff', function (): void {
    $invoice = TestInvoice::create(['number' => 'RE-001', 'status' => TestInvoiceStatus::Draft]);
    $invoice->update(['status' => TestInvoiceStatus::Sent]);

    Livewire::test(ViewActivity::class, ['record' => Activity::query()->latest('id')->first()->getKey()])
        ->assertSuccessful()
        ->assertSee('Entwurf')
        ->assertSee('Versendet');
});

it('filters by event', function (): void {
    $invoice = TestInvoice::create(['number' => 'RE-001']);
    $invoice->update(['number' => 'RE-002']);

    Livewire::test(ListActivities::class)
        ->filterTable('event', 'updated')
        ->assertCanSeeTableRecords(Activity::query()->where('event', 'updated')->get())
        ->assertCanNotSeeTableRecords(Activity::query()->where('event', 'created')->get());
});

it('scopes the list to one record when given a subject', function (): void {
    $first = TestInvoice::create(['number' => 'RE-001']);
    $second = TestInvoice::create(['number' => 'RE-002']);

    Livewire::test(ListActivities::class, [
        'subjectType' => $first->getMorphClass(),
        'subjectId' => (string) $first->getKey(),
    ])
        ->assertSuccessful()
        ->assertCanSeeTableRecords(Activity::query()->where('subject_id', $first->id)->get())
        ->assertCanNotSeeTableRecords(Activity::query()->where('subject_id', $second->id)->get());
});

it('never allows creating an activity by hand', function (): void {
    expect(ActivityResource::canCreate())->toBeFalse();
});

it('applies the plugin query modifier to the resource', function (): void {
    TestInvoice::create(['number' => 'RE-001']);

    ActivityTimelinePlugin::get()->modifyQueryUsing(
        fn ($query) => $query->where('log_name', 'nothing-matches-this')
    );

    expect(ActivityResource::getEloquentQuery()->count())->toBe(0);

    ActivityTimelinePlugin::get()->modifyQueryUsing(null);
});
