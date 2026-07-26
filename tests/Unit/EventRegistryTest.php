<?php

declare(strict_types=1);

use BokshornIt\FilamentActivityTimeline\Support\EventRegistry;

beforeEach(function (): void {
    $this->registry = EventRegistry::make();
});

it('knows the four lifecycle events', function (string $event, string $color): void {
    expect($this->registry->color($event))->toBe($color)
        ->and($this->registry->icon($event))->toStartWith('heroicon-')
        ->and($this->registry->isLifecycleEvent($event))->toBeTrue();
})->with([
    ['created', 'success'],
    ['updated', 'warning'],
    ['deleted', 'danger'],
    ['restored', 'info'],
]);

it('renders a registered custom event with its own icon and colour', function (): void {
    expect($this->registry->color('invoice_sent'))->toBe('info')
        ->and($this->registry->icon('invoice_sent'))->toBe('heroicon-m-paper-airplane')
        ->and($this->registry->isLifecycleEvent('invoice_sent'))->toBeFalse();
});

it('falls back to a neutral icon for an unregistered event', function (): void {
    expect($this->registry->color('something_odd'))->toBe('gray')
        ->and($this->registry->icon('something_odd'))->toBe('heroicon-m-information-circle');
});

it('labels an unregistered event by headline-casing it', function (): void {
    expect($this->registry->label('payment_matched'))->toBe('Payment Matched');
});

it('prefers an application translation for a custom event label', function (): void {
    app('translator')->addLines(['activity_events.invoice_sent' => 'Rechnung versendet'], 'en');

    expect($this->registry->label('invoice_sent'))->toBe('Rechnung versendet');
});

it('offers lifecycle and custom events together as filter options', function (): void {
    expect($this->registry->filterOptions())
        ->toHaveKeys(['created', 'updated', 'deleted', 'restored', 'invoice_sent']);
});
