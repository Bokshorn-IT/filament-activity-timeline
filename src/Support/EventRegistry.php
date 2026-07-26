<?php

declare(strict_types=1);

namespace BokshornIt\FilamentActivityTimeline\Support;

use BokshornIt\FilamentActivityTimeline\ActivityTimelinePlugin;
use Illuminate\Support\Str;

/**
 * Icons, colours and labels for activity events.
 *
 * The four lifecycle events are built in. Domain events like "invoice_sent"
 * get registered on the plugin. Anything unregistered still renders, just
 * with a neutral icon.
 */
class EventRegistry
{
    /**
     * @var array<string, array{icon: string, color: string}>
     */
    protected const DEFAULT_EVENTS = [
        'created' => ['icon' => 'heroicon-m-plus-circle', 'color' => 'success'],
        'updated' => ['icon' => 'heroicon-m-pencil-square', 'color' => 'warning'],
        'deleted' => ['icon' => 'heroicon-m-trash', 'color' => 'danger'],
        'restored' => ['icon' => 'heroicon-m-arrow-uturn-left', 'color' => 'info'],
    ];

    public function __construct(
        protected readonly ActivityTimelinePlugin $plugin,
    ) {}

    public static function make(): static
    {
        return new static(ActivityTimelinePlugin::resolve());
    }

    public function icon(?string $event): string
    {
        return $this->definition($event)['icon'] ?? 'heroicon-m-information-circle';
    }

    public function color(?string $event): string
    {
        return $this->definition($event)['color'] ?? 'gray';
    }

    public function label(?string $event): string
    {
        if ($event === null || $event === '') {
            return $this->plugin->getPlaceholder();
        }

        if (array_key_exists($event, static::DEFAULT_EVENTS)) {
            return __('filament-activity-timeline::activity.events.'.$event);
        }

        $translationKey = $this->plugin->getEventLabelNamespace().'.'.$event;
        $translated = __($translationKey);

        return is_string($translated) && $translated !== $translationKey
            ? $translated
            : Str::headline($event);
    }

    public function isLifecycleEvent(?string $event): bool
    {
        return $event !== null && array_key_exists($event, static::DEFAULT_EVENTS);
    }

    /**
     * Every event that can be filtered on: the four lifecycle events plus
     * whatever the host app registered.
     *
     * @return array<string, string>
     */
    public function filterOptions(): array
    {
        $events = array_merge(
            array_keys(static::DEFAULT_EVENTS),
            array_keys($this->plugin->getEvents()),
        );

        return collect($events)
            ->unique()
            ->mapWithKeys(fn (string $event): array => [$event => $this->label($event)])
            ->all();
    }

    /**
     * @return array{icon?: string, color?: string}
     */
    protected function definition(?string $event): array
    {
        if ($event === null) {
            return [];
        }

        $registered = $this->plugin->getEvents()[$event] ?? [];

        return $registered + (static::DEFAULT_EVENTS[$event] ?? []);
    }
}
