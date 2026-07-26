<?php

declare(strict_types=1);

use BokshornIt\FilamentActivityTimeline\Tests\Fixtures\TestInvoice;
use BokshornIt\FilamentActivityTimeline\Tests\Fixtures\TestInvoiceLine;
use BokshornIt\FilamentActivityTimeline\Tests\Fixtures\TestInvoiceStatus;
use BokshornIt\FilamentActivityTimeline\Tests\Fixtures\TimelineActionProbe;

beforeEach(function (): void {
    $this->action = TimelineActionProbe::make('timeline');
});

it('collects the record itself as a subject', function (): void {
    $invoice = TestInvoice::create(['number' => 'RE-001']);

    expect($this->action->subjectsFor($invoice)->pluck('id')->all())->toBe([$invoice->id]);
});

it('returns only the given record activity by default', function (): void {
    $invoice = TestInvoice::create(['number' => 'RE-001']);
    $other = TestInvoice::create(['number' => 'RE-002']);

    $activities = $this->action->activitiesFor($invoice);

    expect($activities)->toHaveCount(1)
        ->and($activities->first()->subject_id)->toBe($invoice->id)
        ->and($activities->first()->subject_id)->not->toBe($other->id);
});

it('pulls related records into the same timeline', function (): void {
    $invoice = TestInvoice::create(['number' => 'RE-001']);
    TestInvoiceLine::create(['test_invoice_id' => $invoice->id, 'description' => 'Domain .de']);

    $withoutRelations = $this->action->activitiesFor($invoice);

    $withRelations = TimelineActionProbe::make('timeline')
        ->withRelations(['lines'])
        ->activitiesFor($invoice->fresh());

    expect($withoutRelations)->toHaveCount(1)
        ->and($withRelations)->toHaveCount(2)
        ->and($withRelations->pluck('subject_type')->unique())->toHaveCount(2);
});

it('survives a relation that does not exist', function (): void {
    $invoice = TestInvoice::create(['number' => 'RE-001']);

    $activities = TimelineActionProbe::make('timeline')
        ->withRelations(['nonExistentRelation'])
        ->activitiesFor($invoice);

    expect($activities)->toHaveCount(1);
});

it('orders newest first', function (): void {
    $invoice = TestInvoice::create(['number' => 'RE-001']);
    $invoice->update(['status' => TestInvoiceStatus::Sent]);

    expect($this->action->activitiesFor($invoice)->pluck('event')->all())
        ->toBe(['updated', 'created']);
});

it('caps the timeline at the configured limit but still counts the rest', function (): void {
    $invoice = TestInvoice::create(['number' => 'RE-001']);

    foreach (['RE-002', 'RE-003', 'RE-004'] as $number) {
        $invoice->update(['number' => $number]);
    }

    $action = TimelineActionProbe::make('timeline')->limit(2);

    expect($action->activitiesFor($invoice))->toHaveCount(2)
        ->and($action->countFor($invoice))->toBe(4);
});
