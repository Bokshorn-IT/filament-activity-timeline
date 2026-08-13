<?php

declare(strict_types=1);

use BokshornIt\FilamentActivityTimeline\Support\ChangeFormatter;
use BokshornIt\FilamentActivityTimeline\Support\SubjectResolver;
use BokshornIt\FilamentActivityTimeline\Tests\Fixtures\TestCustomer;
use BokshornIt\FilamentActivityTimeline\Tests\Fixtures\TestInvoice;
use BokshornIt\FilamentActivityTimeline\Tests\Fixtures\TestInvoiceLine;
use BokshornIt\FilamentActivityTimeline\Tests\Fixtures\TestInvoiceStatus;
use BokshornIt\FilamentActivityTimeline\Tests\Fixtures\TestNote;
use Illuminate\Database\Eloquent\Relations\Relation;

beforeEach(function (): void {
    $this->formatter = ChangeFormatter::make();
});

it('boots the test harness', function (): void {
    expect($this->formatter)->toBeInstanceOf(ChangeFormatter::class);
});

it('renders an enum cast as its label', function (): void {
    expect($this->formatter->formatValue(TestInvoice::class, 'status', TestInvoiceStatus::Sent->value))
        ->toBe('Versendet');
});

it('renders a date cast in the configured format', function (): void {
    expect($this->formatter->formatValue(TestInvoice::class, 'issue_date', '2026-07-31T00:00:00.000000Z'))
        ->toBe('31.07.2026');
});

it('renders a datetime cast with its time', function (): void {
    expect($this->formatter->formatValue(TestInvoice::class, 'sent_at', '2026-07-31T09:30:00.000000Z'))
        ->toBe('31.07.2026 11:30');
});

it('renders booleans as words', function (): void {
    expect($this->formatter->formatValue(TestInvoice::class, 'is_paid', true))->toBe('yes')
        ->and($this->formatter->formatValue(TestInvoice::class, 'is_paid', false))->toBe('no');
});

it('renders booleans in the active locale', function (): void {
    app()->setLocale('de');

    expect($this->formatter->formatValue(TestInvoice::class, 'is_paid', true))->toBe('ja')
        ->and($this->formatter->formatValue(TestInvoice::class, 'is_paid', false))->toBe('nein');
});

it('renders null and empty values as the placeholder', function (): void {
    expect($this->formatter->formatValue(TestInvoice::class, 'number', null))->toBe('-')
        ->and($this->formatter->formatValue(TestInvoice::class, 'number', ''))->toBe('-');
});

it('resolves a foreign key to the related record title', function (): void {
    $customer = TestCustomer::create(['name' => 'Muster GmbH']);

    expect($this->formatter->formatValue(TestInvoice::class, 'test_customer_id', $customer->id))
        ->toBe('Muster GmbH');
});

it('leaves a foreign key alone when the related record is gone', function (): void {
    expect($this->formatter->formatValue(TestInvoice::class, 'test_customer_id', 999))
        ->toBe('999');
});

it('lets the model render a value the schema cannot explain', function (): void {
    expect($this->formatter->formatValue(TestInvoice::class, 'total', '1234.5'))
        ->toBe('1.234,50 EUR');
});

it('keeps its own handling for the columns a model does not claim', function (): void {
    expect($this->formatter->formatValue(TestInvoice::class, 'status', TestInvoiceStatus::Sent->value))
        ->toBe('Versendet');
});

it('settles empty values before asking the model', function (): void {
    expect($this->formatter->formatValue(TestInvoice::class, 'total', null))->toBe('-');
});

it('falls through when a model value formatter throws', function (): void {
    expect($this->formatter->formatValue(TestInvoice::class, 'unrenderable', 'RE-001'))
        ->toBe('RE-001');
});

it('leaves models that render nothing of their own alone', function (): void {
    expect($this->formatter->formatValue(TestInvoiceLine::class, 'description', 'Beratung'))
        ->toBe('Beratung');
});

it('resolves a morph column from the type logged beside it', function (): void {
    $invoice = TestInvoice::create(['number' => 'RE-100']);

    expect($this->formatter->formatValue(TestNote::class, 'notable_id', $invoice->id, [
        'notable_type' => TestInvoice::class,
    ]))->toBe('RE-100');
});

it('leaves a morph column alone when its type was not logged with it', function (): void {
    $invoice = TestInvoice::create(['number' => 'RE-101']);

    expect($this->formatter->formatValue(TestNote::class, 'notable_id', $invoice->id))
        ->toBe((string) $invoice->id);
});

it('resolves a morph column through a morph map alias', function (): void {
    Relation::morphMap(['invoice' => TestInvoice::class]);

    $invoice = TestInvoice::create(['number' => 'RE-102']);

    expect($this->formatter->formatValue(TestNote::class, 'notable_id', $invoice->id, [
        'notable_type' => 'invoice',
    ]))->toBe('RE-102');

    Relation::morphMap([], merge: false);
});

it('labels a morph type given as a morph map alias', function (): void {
    Relation::morphMap(['invoice' => TestInvoice::class]);

    expect($this->formatter->formatValue(TestNote::class, 'notable_type', 'invoice'))
        ->toBe('Test Invoice');

    Relation::morphMap([], merge: false);
});

it('still names a foreign key whose record was soft deleted', function (): void {
    $invoice = TestInvoice::create(['number' => 'RE-103']);
    $invoice->delete();

    expect($this->formatter->formatValue(TestNote::class, 'notable_id', $invoice->id, [
        'notable_type' => TestInvoice::class,
    ]))->toBe('RE-103');
});

it('falls back to a headline-cased field label when no translation exists', function (): void {
    expect($this->formatter->fieldLabel('test_customer_id'))->toBe('Test Customer Id');
});

it('prefers the application field label translation when one exists', function (): void {
    app('translator')->addLines(['changes.total' => 'Bruttobetrag'], 'de');
    app()->setLocale('de');

    expect($this->formatter->fieldLabel('total'))->toBe('Bruttobetrag');
});

it('labels a model with no resource from the subject label namespace', function (): void {
    app('translator')->addLines(['activity_subjects.test_invoice_line' => 'Rechnungsposition'], 'en');

    expect(SubjectResolver::make()->typeLabel(TestInvoiceLine::class))->toBe('Rechnungsposition');
});

it('falls back to the class name when no subject label is translated', function (): void {
    expect(SubjectResolver::make()->typeLabel(TestInvoiceLine::class))->toBe('Test Invoice Line');
});
