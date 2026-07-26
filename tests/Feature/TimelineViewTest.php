<?php

declare(strict_types=1);

use BokshornIt\FilamentActivityTimeline\Tests\Fixtures\TestCustomer;
use BokshornIt\FilamentActivityTimeline\Tests\Fixtures\TestInvoice;
use BokshornIt\FilamentActivityTimeline\Tests\Fixtures\TestInvoiceStatus;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Spatie\Activitylog\Models\Activity;

function renderTimeline(Collection $activities, ?int $total = null, ?string $showAllUrl = null): string
{
    return view('filament-activity-timeline::timeline', [
        'activities' => $activities,
        'total' => $total ?? $activities->count(),
        'shown' => $activities->count(),
        'showAllUrl' => $showAllUrl,
    ])->render();
}

it('renders an entry with its event, subject and diff', function (): void {
    $invoice = TestInvoice::create(['number' => 'RE-001', 'status' => TestInvoiceStatus::Draft]);
    $invoice->update(['status' => TestInvoiceStatus::Sent]);

    $html = renderTimeline(Activity::query()->with(['causer', 'subject'])->latest('id')->get());

    expect($html)
        ->toContain('Updated')
        ->toContain('RE-001')
        ->toContain('Entwurf')
        ->toContain('Versendet');
});

it('renders the empty state when nothing has been logged', function (): void {
    expect(renderTimeline(collect()))
        ->toContain('Nothing has been logged for this record yet.');
});

it('names the causer, or System when there is none', function (): void {
    $customer = TestCustomer::create(['name' => 'Muster GmbH']);
    $invoice = TestInvoice::create(['number' => 'RE-001']);

    activity()->performedOn($invoice)->by($customer)->event('updated')->log('changed');

    $html = renderTimeline(Activity::query()->with(['causer', 'subject'])->latest('id')->get());

    expect($html)->toContain('Muster GmbH')
        ->and($html)->toContain('System');
});

it('renders a registered custom event with its own colour', function (): void {
    $invoice = TestInvoice::create(['number' => 'RE-001']);

    activity()->performedOn($invoice)->event('invoice_sent')->log('sent');

    $html = renderTimeline(Activity::query()->with(['causer', 'subject'])->where('event', 'invoice_sent')->get());

    expect($html)->toContain('fi-color-info');
});

it('notes how many entries were cut off and links to the rest', function (): void {
    $invoice = TestInvoice::create(['number' => 'RE-001']);

    $html = renderTimeline(Activity::query()->with(['causer', 'subject'])->get(), total: 12, showAllUrl: 'https://example.test/log');

    expect($html)
        ->toContain('1 of 12 entries')
        ->toContain('Show all')
        ->toContain('https://example.test/log');
});

it('resolves a repeated foreign key once for the whole timeline', function (): void {
    $first = TestCustomer::create(['name' => 'Muster GmbH']);
    $second = TestCustomer::create(['name' => 'Beispiel AG']);

    $invoice = TestInvoice::create(['number' => 'RE-001', 'test_customer_id' => $first->id]);

    // Four entries, every one of them naming the same two customers.
    foreach ([$second, $first, $second, $first] as $customer) {
        $invoice->update(['test_customer_id' => $customer->id]);
    }

    $activities = Activity::query()
        ->with(['causer', 'subject'])
        ->where('subject_type', TestInvoice::class)
        ->latest('id')
        ->get();

    DB::flushQueryLog();
    DB::enableQueryLog();

    renderTimeline($activities);

    $customerQueries = collect(DB::getQueryLog())
        ->filter(fn (array $query): bool => str_contains($query['query'], 'test_customers'))
        ->count();

    DB::disableQueryLog();

    // One lookup per distinct customer, not one per entry that mentions them.
    // Five entries: the creation plus the four reassignments.
    expect($activities)->toHaveCount(5)
        ->and($customerQueries)->toBe(2);
});

it('omits the truncation notice when everything is shown', function (): void {
    TestInvoice::create(['number' => 'RE-001']);

    expect(renderTimeline(Activity::query()->with(['causer', 'subject'])->get()))->not->toContain('Show all');
});
