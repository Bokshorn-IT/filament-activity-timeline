<?php

declare(strict_types=1);

use BokshornIt\FilamentActivityTimeline\Support\CauserResolver;
use BokshornIt\FilamentActivityTimeline\Tests\Fixtures\TestCustomer;
use BokshornIt\FilamentActivityTimeline\Tests\Fixtures\TestUser;

beforeEach(function (): void {
    $this->causers = CauserResolver::make();
});

it('renders an absent causer as System', function (): void {
    expect($this->causers->label(null))->toBe('System')
        ->and($this->causers->icon(null))->toBe('heroicon-m-cog-6-tooth');
});

it('names a causer through the activity title contract', function (): void {
    $customer = TestCustomer::create(['name' => 'Muster GmbH']);

    expect($this->causers->label($customer))->toBe('Muster GmbH');
});

it('falls back to the name attribute for a causer without the contract', function (): void {
    $user = TestUser::create(['name' => 'Johannes', 'email' => 'j@example.test']);

    expect($this->causers->label($user))->toBe('Johannes');
});

it('gives each causer type its configured icon', function (): void {
    $customer = TestCustomer::create(['name' => 'Muster GmbH']);
    $user = TestUser::create(['name' => 'Johannes']);

    expect($this->causers->icon($customer))->toBe('heroicon-m-building-office')
        ->and($this->causers->icon($user))->toBe('heroicon-m-user');
});

it('tells a portal customer apart from a user', function (): void {
    $customer = TestCustomer::create(['name' => 'Muster GmbH']);
    $user = TestUser::create(['name' => 'Johannes']);

    expect($this->causers->icon($customer))->not->toBe($this->causers->icon($user));
});
