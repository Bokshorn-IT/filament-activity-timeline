<?php

declare(strict_types=1);

namespace BokshornIt\FilamentActivityTimeline\Tests\Fixtures\TestContractResource\Pages;

use BokshornIt\FilamentActivityTimeline\Tests\Fixtures\TestContractResource;
use Filament\Resources\Pages\ViewRecord;

class ViewTestContract extends ViewRecord
{
    protected static string $resource = TestContractResource::class;
}
