<?php

declare(strict_types=1);

namespace BokshornIt\FilamentActivityTimeline\Tests\Fixtures;

use BokshornIt\FilamentActivityTimeline\Tests\Fixtures\TestContractResource\Pages\ViewTestContract;
use Filament\Resources\Pages\PageRegistration;
use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Builder;

class TestContractResource extends Resource
{
    protected static ?string $model = TestDocument::class;

    protected static ?string $modelLabel = 'Contract';

    protected static ?string $slug = 'test-contracts';

    protected static bool $shouldRegisterNavigation = false;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('status', 'signed');
    }

    /**
     * @return array<string, PageRegistration>
     */
    public static function getPages(): array
    {
        return [
            'view' => ViewTestContract::route('/{record}'),
        ];
    }
}
