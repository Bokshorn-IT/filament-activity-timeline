<?php

declare(strict_types=1);

namespace Workbench\App\Filament\Resources\ArticleResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Workbench\App\Filament\Resources\ArticleResource;

class ListArticles extends ListRecords
{
    protected static string $resource = ArticleResource::class;
}
