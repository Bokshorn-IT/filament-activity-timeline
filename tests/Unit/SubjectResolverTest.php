<?php

declare(strict_types=1);

use BokshornIt\FilamentActivityTimeline\Support\SubjectResolver;
use BokshornIt\FilamentActivityTimeline\Tests\Fixtures\TestDocument;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Activitylog\Models\Activity;

beforeEach(function (): void {
    Schema::create('test_documents', function (Blueprint $table): void {
        $table->id();
        $table->string('number');
        $table->string('status');
        $table->timestamps();
    });

    $this->subjects = SubjectResolver::make();
});

it('labels and links a record its resource shows', function (): void {
    $document = TestDocument::create(['number' => 'D-1', 'status' => 'signed']);
    $activity = Activity::query()->latest('id')->first();

    expect($this->subjects->label($activity))->toBe('Contract: D-1')
        ->and($this->subjects->url($activity))->toEndWith("/testing/test-contracts/{$document->id}");
});

it('lets a record its resource scopes out label itself and does not link it', function (): void {
    TestDocument::create(['number' => 'D-2', 'status' => 'draft']);
    $activity = Activity::query()->latest('id')->first();

    expect($this->subjects->label($activity))->toBe('Draft: D-2')
        ->and($this->subjects->url($activity))->toBeNull();
});

it('falls back to the resource label once the record is gone', function (): void {
    $document = TestDocument::create(['number' => 'D-2', 'status' => 'draft']);
    $document->delete();

    $activity = Activity::query()->latest('id')->first();

    expect($this->subjects->label($activity))->toBe("Contract #{$document->id}");
});
