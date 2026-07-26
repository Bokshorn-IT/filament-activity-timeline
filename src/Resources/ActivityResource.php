<?php

declare(strict_types=1);

namespace BokshornIt\FilamentActivityTimeline\Resources;

use BackedEnum;
use BokshornIt\FilamentActivityTimeline\ActivityTimelinePlugin;
use BokshornIt\FilamentActivityTimeline\Resources\Pages\ListActivities;
use BokshornIt\FilamentActivityTimeline\Resources\Pages\ViewActivity;
use BokshornIt\FilamentActivityTimeline\Support\CauserResolver;
use BokshornIt\FilamentActivityTimeline\Support\EventRegistry;
use BokshornIt\FilamentActivityTimeline\Support\SubjectResolver;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ViewEntry;
use Filament\Resources\Pages\PageRegistration;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Spatie\Activitylog\Models\Activity;

class ActivityResource extends Resource
{
    protected static ?string $model = Activity::class;

    protected static bool $isGloballySearchable = false;

    public static function getModelLabel(): string
    {
        return __('filament-activity-timeline::activity.model.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('filament-activity-timeline::activity.model.plural');
    }

    public static function getNavigationGroup(): ?string
    {
        return ActivityTimelinePlugin::resolve()->getNavigationGroup();
    }

    public static function getNavigationIcon(): string|BackedEnum|null
    {
        return ActivityTimelinePlugin::resolve()->getNavigationIcon();
    }

    public static function getNavigationSort(): ?int
    {
        return ActivityTimelinePlugin::resolve()->getNavigationSort();
    }

    public static function shouldRegisterNavigation(): bool
    {
        return ActivityTimelinePlugin::resolve()->hasNavigation();
    }

    public static function getEloquentQuery(): Builder
    {
        return ActivityTimelinePlugin::resolve()->applyQueryModifier(
            parent::getEloquentQuery()->with(['subject', 'causer'])
        );
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function infolist(Schema $schema): Schema
    {
        $events = EventRegistry::make();
        $subjects = SubjectResolver::make();
        $causers = CauserResolver::make();

        return $schema->components([
            Section::make()
                ->columns(2)
                ->schema([
                    TextEntry::make('event')
                        ->label(__('filament-activity-timeline::activity.fields.event'))
                        ->badge()
                        ->color(fn (?string $state): string => $events->color($state))
                        ->icon(fn (?string $state): string => $events->icon($state))
                        ->formatStateUsing(fn (?string $state): string => $events->label($state)),
                    TextEntry::make('created_at')
                        ->label(__('filament-activity-timeline::activity.fields.created_at'))
                        ->dateTime(ActivityTimelinePlugin::resolve()->getDateTimeFormat())
                        ->icon('heroicon-m-clock'),
                    TextEntry::make('subject_label')
                        ->label(__('filament-activity-timeline::activity.fields.subject'))
                        ->state(fn (Activity $record): string => $subjects->label($record))
                        ->icon('heroicon-m-cube')
                        ->url(fn (Activity $record): ?string => $subjects->url($record)),
                    TextEntry::make('causer_label')
                        ->label(__('filament-activity-timeline::activity.fields.causer'))
                        ->state(fn (Activity $record): string => $causers->label($record->causer))
                        ->icon(fn (Activity $record): string => $causers->icon($record->causer)),
                    TextEntry::make('log_name')
                        ->label(__('filament-activity-timeline::activity.fields.log_name'))
                        ->badge()
                        ->color('gray')
                        ->placeholder(ActivityTimelinePlugin::resolve()->getPlaceholder()),
                    TextEntry::make('description')
                        ->label(__('filament-activity-timeline::activity.fields.description'))
                        ->visible(fn (Activity $record): bool => filled($record->description)
                            && $record->description !== $record->event)
                        ->columnSpanFull(),
                ]),
            Section::make(__('filament-activity-timeline::activity.sections.changes'))
                ->icon('heroicon-m-arrows-right-left')
                ->schema([
                    ViewEntry::make('properties')
                        ->hiddenLabel()
                        ->view('filament-activity-timeline::changes'),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        $events = EventRegistry::make();
        $subjects = SubjectResolver::make();
        $causers = CauserResolver::make();
        $plugin = ActivityTimelinePlugin::resolve();

        return $table
            ->columns([
                TextColumn::make('created_at')
                    ->label(__('filament-activity-timeline::activity.fields.created_at'))
                    ->dateTime($plugin->getDateTimeFormat())
                    ->description(fn (Activity $record): ?string => $record->created_at?->diffForHumans())
                    ->sortable(),
                TextColumn::make('event')
                    ->label(__('filament-activity-timeline::activity.fields.event'))
                    ->badge()
                    ->color(fn (?string $state): string => $events->color($state))
                    ->icon(fn (?string $state): string => $events->icon($state))
                    ->formatStateUsing(fn (?string $state): string => $events->label($state))
                    ->sortable(),
                // Deliberately not named after the subject/causer morph
                // relations: when one of those resolves to null, Filament
                // renders the placeholder without ever calling state().
                TextColumn::make('subject_label')
                    ->label(__('filament-activity-timeline::activity.fields.subject'))
                    ->state(fn (Activity $record): string => $subjects->label($record))
                    ->wrap()
                    ->searchable(['subject_type']),
                TextColumn::make('causer_label')
                    ->label(__('filament-activity-timeline::activity.fields.causer'))
                    ->state(fn (Activity $record): string => $causers->label($record->causer))
                    ->icon(fn (Activity $record): string => $causers->icon($record->causer))
                    ->iconColor('gray'),
                TextColumn::make('description')
                    ->label(__('filament-activity-timeline::activity.fields.description'))
                    ->wrap()
                    ->limit(60)
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('log_name')
                    ->label(__('filament-activity-timeline::activity.fields.log_name'))
                    ->badge()
                    ->color('gray')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('event')
                    ->label(__('filament-activity-timeline::activity.fields.event'))
                    ->options(fn (): array => $events->filterOptions()),
                SelectFilter::make('subject_type')
                    ->label(__('filament-activity-timeline::activity.fields.subject_type'))
                    ->options(fn (): array => $subjects->filterOptions()),
                SelectFilter::make('causer_id')
                    ->label(__('filament-activity-timeline::activity.fields.causer'))
                    ->options(fn (): array => static::causerOptions())
                    ->searchable(),
                Filter::make('created_at')
                    ->schema([
                        DatePicker::make('created_from')
                            ->label(__('filament-activity-timeline::activity.fields.date_from'))
                            ->native(false),
                        DatePicker::make('created_until')
                            ->label(__('filament-activity-timeline::activity.fields.date_until'))
                            ->native(false),
                    ])
                    ->columns(2)
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when(
                            $data['created_from'] ?? null,
                            fn (Builder $query, string $date): Builder => $query->whereDate('created_at', '>=', $date)
                        )
                        ->when(
                            $data['created_until'] ?? null,
                            fn (Builder $query, string $date): Builder => $query->whereDate('created_at', '<=', $date)
                        ))
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];

                        if ($data['created_from'] ?? null) {
                            $indicators[] = __('filament-activity-timeline::activity.indicators.from')
                                .' '.Carbon::parse($data['created_from'])->format(ActivityTimelinePlugin::resolve()->getDateFormat());
                        }

                        if ($data['created_until'] ?? null) {
                            $indicators[] = __('filament-activity-timeline::activity.indicators.until')
                                .' '.Carbon::parse($data['created_until'])->format(ActivityTimelinePlugin::resolve()->getDateFormat());
                        }

                        return $indicators;
                    }),
            ])
            ->filtersFormColumns(2)
            ->recordActions([
                ViewAction::make(),
            ])
            ->emptyStateHeading(__('filament-activity-timeline::activity.empty.heading'))
            ->emptyStateDescription(__('filament-activity-timeline::activity.empty.description'))
            ->emptyStateIcon('heroicon-o-archive-box');
    }

    /**
     * Only causers that actually appear in the log.
     *
     * Built from a fresh query rather than getEloquentQuery(), whose eager
     * load of the subject would need columns this one does not select. Under
     * Model::shouldBeStrict() that combination throws.
     *
     * @return array<string, string>
     */
    protected static function causerOptions(): array
    {
        $causers = CauserResolver::make();

        return ActivityTimelinePlugin::resolve()
            ->applyQueryModifier(Activity::query())
            ->whereNotNull('causer_id')
            ->select(['causer_type', 'causer_id'])
            ->distinct()
            ->with('causer')
            ->get()
            ->filter(fn (Activity $activity): bool => $activity->causer !== null)
            ->mapWithKeys(fn (Activity $activity): array => [
                $activity->causer_id => $causers->label($activity->causer),
            ])
            ->sort()
            ->all();
    }

    /**
     * @return array<int, string>
     */
    public static function getRelations(): array
    {
        return [];
    }

    /**
     * @return array<string, PageRegistration>
     */
    public static function getPages(): array
    {
        return [
            'index' => ListActivities::route('/'),
            'view' => ViewActivity::route('/{record}'),
        ];
    }
}
