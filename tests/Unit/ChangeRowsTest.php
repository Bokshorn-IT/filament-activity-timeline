<?php

declare(strict_types=1);

use BokshornIt\FilamentActivityTimeline\ActivityTimelinePlugin;
use BokshornIt\FilamentActivityTimeline\Support\ChangeFormatter;
use BokshornIt\FilamentActivityTimeline\Tests\Fixtures\TestCustomer;
use BokshornIt\FilamentActivityTimeline\Tests\Fixtures\TestInvoice;
use BokshornIt\FilamentActivityTimeline\Tests\Fixtures\TestInvoiceStatus;
use Spatie\Activitylog\Models\Activity;

it('builds a diff row marking what actually changed', function (): void {
    $invoice = TestInvoice::create(['number' => 'RE-001', 'status' => TestInvoiceStatus::Draft]);
    $invoice->update(['status' => TestInvoiceStatus::Sent]);

    $rows = ChangeFormatter::make()->rows(Activity::query()->latest('id')->first());

    expect($rows)->toHaveCount(1)
        ->and($rows->first())->toMatchArray([
            'label' => 'Status',
            'old' => 'Entwurf',
            'new' => 'Versendet',
            'changed' => true,
        ]);
});

it('marks a first-time value as unchanged but still shows it', function (): void {
    TestInvoice::create(['number' => 'RE-002', 'status' => TestInvoiceStatus::Draft]);

    $rows = ChangeFormatter::make()->rows(Activity::query()->latest('id')->first());

    expect($rows->pluck('changed')->unique()->all())->toBe([false])
        ->and($rows->pluck('new'))->toContain('RE-002', 'Entwurf');
});

it('drops rows that neither changed nor hold a value', function (): void {
    TestInvoice::create(['number' => 'RE-003']);

    $rows = ChangeFormatter::make()->rows(Activity::query()->latest('id')->first());

    expect($rows->pluck('new'))->not->toContain('-');
});

it('resolves a foreign key inside a diff row', function (): void {
    $customer = TestCustomer::create(['name' => 'Muster GmbH']);
    $other = TestCustomer::create(['name' => 'Beispiel AG']);

    $invoice = TestInvoice::create(['number' => 'RE-004', 'test_customer_id' => $customer->id]);
    $invoice->update(['test_customer_id' => $other->id]);

    $row = ChangeFormatter::make()
        ->rows(Activity::query()->latest('id')->first())
        ->firstWhere('label', 'Test Customer Id');

    expect($row)->not->toBeNull()
        ->and($row['old'])->toBe('Muster GmbH')
        ->and($row['new'])->toBe('Beispiel AG');
});

it('omits keys the plugin was told to ignore', function (): void {
    ActivityTimelinePlugin::get()->ignoredKeys(['total']);

    $invoice = TestInvoice::create(['number' => 'RE-005', 'total' => 100]);
    $invoice->update(['total' => 200]);

    $labels = ChangeFormatter::make()
        ->rows(Activity::query()->latest('id')->first())
        ->pluck('label');

    expect($labels)->not->toContain('Total');

    ActivityTimelinePlugin::get()->ignoredKeys([]);
});
