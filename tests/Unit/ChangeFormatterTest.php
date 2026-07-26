<?php

declare(strict_types=1);

use BokshornIt\FilamentActivityTimeline\Support\ChangeFormatter;
use BokshornIt\FilamentActivityTimeline\Support\SubjectResolver;
use BokshornIt\FilamentActivityTimeline\Tests\Fixtures\TestCustomer;
use BokshornIt\FilamentActivityTimeline\Tests\Fixtures\TestInvoice;
use BokshornIt\FilamentActivityTimeline\Tests\Fixtures\TestInvoiceLine;
use BokshornIt\FilamentActivityTimeline\Tests\Fixtures\TestInvoiceStatus;

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
