<?php

declare(strict_types=1);

namespace Workbench\App\Filament\Resources;

use BackedEnum;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Pages\PageRegistration;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Workbench\App\Filament\Resources\ArticleResource\Pages\EditArticle;
use Workbench\App\Filament\Resources\ArticleResource\Pages\ListArticles;
use Workbench\App\Models\Article;
use Workbench\App\Models\ArticleStatus;

class ArticleResource extends Resource
{
    protected static ?string $model = Article::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $modelLabel = 'Article';

    protected static ?string $pluralModelLabel = 'Articles';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('title')->required()->columnSpanFull(),
            Select::make('status')->options(ArticleStatus::class)->required(),
            Select::make('author_id')->relationship('author', 'name')->label('Author'),
            DatePicker::make('due_date')->native(false),
            DateTimePicker::make('published_at')->native(false),
            Toggle::make('is_featured'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')->searchable()->sortable(),
                TextColumn::make('status')->badge()->sortable(),
                TextColumn::make('author.name')->label('Author'),
                TextColumn::make('due_date')->date('d.m.Y')->sortable(),
                TextColumn::make('updated_at')->dateTime('d.m.Y H:i')->sortable(),
            ])
            ->defaultSort('updated_at', 'desc');
    }

    /**
     * @return array<string, PageRegistration>
     */
    public static function getPages(): array
    {
        return [
            'index' => ListArticles::route('/'),
            'edit' => EditArticle::route('/{record}/edit'),
        ];
    }
}
