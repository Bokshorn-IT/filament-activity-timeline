<?php

declare(strict_types=1);

namespace BokshornIt\FilamentActivityTimeline;

use BokshornIt\FilamentActivityTimeline\Resources\ActivityResource;
use Closure;
use Filament\Contracts\Plugin;
use Filament\Facades\Filament;
use Filament\Panel;
use Filament\Support\Concerns\EvaluatesClosures;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ActivityTimelinePlugin implements Plugin
{
    use EvaluatesClosures;

    public const ID = 'filament-activity-timeline';

    protected ?string $resource = null;

    protected string|Closure|null $navigationGroup = null;

    protected string|Closure|null $navigationIcon = null;

    protected int|Closure|null $navigationSort = null;

    protected bool|Closure|null $hasNavigation = null;

    protected ?Closure $modifyQueryUsing = null;

    /** @var array<string, array{icon?: string, color?: string}>|null */
    protected ?array $events = null;

    /** @var array<int, class-string<Model>>|null */
    protected ?array $restorable = null;

    /** @var array<class-string, string>|null */
    protected ?array $causerIcons = null;

    protected ?string $systemCauserIcon = null;

    protected ?string $defaultCauserIcon = null;

    protected ?string $fieldLabelNamespace = null;

    protected ?string $eventLabelNamespace = null;

    protected ?string $subjectLabelNamespace = null;

    /** @var array<int, string>|null */
    protected ?array $ignoredKeys = null;

    protected ?string $dateFormat = null;

    protected ?string $dateTimeFormat = null;

    protected ?string $placeholder = null;

    protected int|Closure|null $timelineLimit = null;

    public static function make(): static
    {
        return app(static::class);
    }

    public static function get(): static
    {
        /** @var static $plugin */
        $plugin = filament(static::ID);

        return $plugin;
    }

    /**
     * The plugin from the current panel, or a plain instance when there is no
     * panel around (console, queue, tests). Every getter falls back to config,
     * so the plain instance behaves like an unconfigured plugin.
     */
    public static function resolve(): static
    {
        $panel = Filament::getCurrentPanel();

        if ($panel?->hasPlugin(static::ID)) {
            /** @var static $plugin */
            $plugin = $panel->getPlugin(static::ID);

            return $plugin;
        }

        return app(static::class);
    }

    public function getId(): string
    {
        return static::ID;
    }

    public function register(Panel $panel): void
    {
        $panel->resources([
            $this->getResource(),
        ]);
    }

    public function boot(Panel $panel): void
    {
        //
    }

    /**
     * @param  class-string<ActivityResource>  $resource
     */
    public function resource(string $resource): static
    {
        $this->resource = $resource;

        return $this;
    }

    /**
     * @return class-string<ActivityResource>
     */
    public function getResource(): string
    {
        return $this->resource
            ?? config('filament-activity-timeline.resource')
            ?? ActivityResource::class;
    }

    public function navigationGroup(string|Closure|null $group): static
    {
        $this->navigationGroup = $group;

        return $this;
    }

    public function getNavigationGroup(): ?string
    {
        return $this->evaluate($this->navigationGroup)
            ?? config('filament-activity-timeline.navigation.group');
    }

    public function navigationIcon(string|Closure|null $icon): static
    {
        $this->navigationIcon = $icon;

        return $this;
    }

    public function getNavigationIcon(): ?string
    {
        return $this->evaluate($this->navigationIcon)
            ?? config('filament-activity-timeline.navigation.icon', 'heroicon-o-archive-box');
    }

    public function navigationSort(int|Closure|null $sort): static
    {
        $this->navigationSort = $sort;

        return $this;
    }

    public function getNavigationSort(): ?int
    {
        return $this->evaluate($this->navigationSort)
            ?? config('filament-activity-timeline.navigation.sort');
    }

    public function registerNavigation(bool|Closure $condition = true): static
    {
        $this->hasNavigation = $condition;

        return $this;
    }

    public function hasNavigation(): bool
    {
        return $this->evaluate($this->hasNavigation)
            ?? config('filament-activity-timeline.navigation.enabled', true);
    }

    /**
     * Applied to both the activity resource and every record timeline, so a
     * scope can never be enforced in one place and forgotten in the other.
     * Multi-tenant apps filter by tenant here.
     */
    public function modifyQueryUsing(?Closure $callback): static
    {
        $this->modifyQueryUsing = $callback;

        return $this;
    }

    public function applyQueryModifier(Builder $query): Builder
    {
        if ($this->modifyQueryUsing === null) {
            return $query;
        }

        return $this->evaluate($this->modifyQueryUsing, [
            'query' => $query,
            'builder' => $query,
        ]) ?? $query;
    }

    /**
     * @param  array<string, array{icon?: string, color?: string}>  $events
     */
    public function events(array $events): static
    {
        $this->events = $events;

        return $this;
    }

    /**
     * @return array<string, array{icon?: string, color?: string}>
     */
    public function getEvents(): array
    {
        return $this->events
            ?? config('filament-activity-timeline.events', []);
    }

    /**
     * Models whose logged "old" values may be written back onto the record.
     *
     * @param  array<int, class-string<Model>>  $models
     */
    public function restorable(array $models): static
    {
        $this->restorable = $models;

        return $this;
    }

    /**
     * @return array<int, class-string<Model>>
     */
    public function getRestorable(): array
    {
        return $this->restorable
            ?? config('filament-activity-timeline.restorable', []);
    }

    /**
     * @param  array<class-string, string>  $icons
     */
    public function causerIcons(array $icons): static
    {
        $this->causerIcons = $icons;

        return $this;
    }

    /**
     * @return array<class-string, string>
     */
    public function getCauserIcons(): array
    {
        return $this->causerIcons
            ?? config('filament-activity-timeline.causer_icons', []);
    }

    public function systemCauserIcon(string $icon): static
    {
        $this->systemCauserIcon = $icon;

        return $this;
    }

    public function getSystemCauserIcon(): string
    {
        return $this->systemCauserIcon
            ?? config('filament-activity-timeline.system_causer_icon', 'heroicon-m-cog-6-tooth');
    }

    public function defaultCauserIcon(string $icon): static
    {
        $this->defaultCauserIcon = $icon;

        return $this;
    }

    public function getDefaultCauserIcon(): string
    {
        return $this->defaultCauserIcon
            ?? config('filament-activity-timeline.default_causer_icon', 'heroicon-m-user');
    }

    public function fieldLabelNamespace(string $namespace): static
    {
        $this->fieldLabelNamespace = $namespace;

        return $this;
    }

    public function getFieldLabelNamespace(): string
    {
        return $this->fieldLabelNamespace
            ?? config('filament-activity-timeline.field_label_namespace', 'changes');
    }

    public function subjectLabelNamespace(string $namespace): static
    {
        $this->subjectLabelNamespace = $namespace;

        return $this;
    }

    public function getSubjectLabelNamespace(): string
    {
        return $this->subjectLabelNamespace
            ?? config('filament-activity-timeline.subject_label_namespace', 'activity_subjects');
    }

    public function eventLabelNamespace(string $namespace): static
    {
        $this->eventLabelNamespace = $namespace;

        return $this;
    }

    public function getEventLabelNamespace(): string
    {
        return $this->eventLabelNamespace
            ?? config('filament-activity-timeline.event_label_namespace', 'activity_events');
    }

    /**
     * @param  array<int, string>  $keys
     */
    public function ignoredKeys(array $keys): static
    {
        $this->ignoredKeys = $keys;

        return $this;
    }

    /**
     * @return array<int, string>
     */
    public function getIgnoredKeys(): array
    {
        return $this->ignoredKeys
            ?? config('filament-activity-timeline.ignored_keys', []);
    }

    public function dateFormat(string $format): static
    {
        $this->dateFormat = $format;

        return $this;
    }

    public function getDateFormat(): string
    {
        return $this->dateFormat
            ?? config('filament-activity-timeline.date_format', 'd.m.Y');
    }

    public function dateTimeFormat(string $format): static
    {
        $this->dateTimeFormat = $format;

        return $this;
    }

    public function getDateTimeFormat(): string
    {
        return $this->dateTimeFormat
            ?? config('filament-activity-timeline.datetime_format', 'd.m.Y H:i');
    }

    public function placeholder(string $placeholder): static
    {
        $this->placeholder = $placeholder;

        return $this;
    }

    public function getPlaceholder(): string
    {
        return $this->placeholder
            ?? config('filament-activity-timeline.placeholder', '-');
    }

    public function timelineLimit(int|Closure|null $limit): static
    {
        $this->timelineLimit = $limit;

        return $this;
    }

    public function getTimelineLimit(): ?int
    {
        return $this->evaluate($this->timelineLimit)
            ?? config('filament-activity-timeline.timeline_limit', 50);
    }
}
