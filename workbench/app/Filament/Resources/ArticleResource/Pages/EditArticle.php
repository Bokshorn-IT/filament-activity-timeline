<?php

declare(strict_types=1);

namespace Workbench\App\Filament\Resources\ArticleResource\Pages;

use BokshornIt\FilamentActivityTimeline\Actions\ActivityTimelineAction;
use Filament\Actions\Action;
use Filament\Resources\Pages\EditRecord;
use Workbench\App\Filament\Resources\ArticleResource;

class EditArticle extends EditRecord
{
    protected static string $resource = ArticleResource::class;

    /**
     * @return array<int, Action>
     */
    protected function getHeaderActions(): array
    {
        return [
            ActivityTimelineAction::make()
                ->withRelations(['revisions']),
        ];
    }
}
