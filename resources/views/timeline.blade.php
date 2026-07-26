@php
    use BokshornIt\FilamentActivityTimeline\Support\CauserResolver;
    use BokshornIt\FilamentActivityTimeline\Support\ChangeFormatter;
    use BokshornIt\FilamentActivityTimeline\Support\EventRegistry;
    use BokshornIt\FilamentActivityTimeline\Support\SubjectResolver;

    $events = EventRegistry::make();
    $subjects = SubjectResolver::make();
    $causers = CauserResolver::make();
    $formatter = ChangeFormatter::make();
@endphp

{{--
    Filament's .fi-color-{name} classes define --color-50..950, so the icon
    picks up whatever palette the panel registered and we do not have to
    generate any colour classes ourselves.
--}}
<div class="space-y-0">
    @forelse ($activities as $activity)
        <div class="relative flex gap-x-4 pb-6 last:pb-0">
            @unless ($loop->last)
                <span
                    class="absolute left-[1.0625rem] top-9 bottom-0 w-px bg-gray-200 dark:bg-white/10"
                    aria-hidden="true"
                ></span>
            @endunless

            <span
                class="fi-color fi-color-{{ $events->color($activity->event) }} relative flex h-9 w-9 flex-none items-center justify-center rounded-full bg-white ring-1 ring-gray-200 dark:bg-gray-900 dark:ring-white/10"
                style="color: var(--color-500)"
            >
                <x-filament::icon :icon="$events->icon($activity->event)" class="h-5 w-5" />
            </span>

            <div class="min-w-0 flex-1">
                <div class="flex flex-wrap items-center justify-between gap-x-2 gap-y-1">
                    <div class="flex flex-wrap items-center gap-2">
                        <x-filament::badge :color="$events->color($activity->event)">
                            {{ $events->label($activity->event) }}
                        </x-filament::badge>

                        <span class="text-sm text-gray-500 dark:text-gray-400">
                            {{ $subjects->label($activity) }}
                        </span>
                    </div>

                    <time
                        class="whitespace-nowrap text-xs text-gray-400 dark:text-gray-500"
                        datetime="{{ $activity->created_at?->toIso8601String() }}"
                        title="{{ $activity->created_at?->format(__('filament-activity-timeline::activity.formats.datetime_full')) }}"
                    >
                        {{ $activity->created_at?->diffForHumans() }}
                    </time>
                </div>

                <p class="mt-1 flex items-center gap-1 text-xs text-gray-500 dark:text-gray-400">
                    <x-filament::icon :icon="$causers->icon($activity->causer)" class="h-3.5 w-3.5 flex-none" />
                    {{ $causers->label($activity->causer) }}
                </p>

                @if (filled($activity->description) && $activity->description !== $activity->event)
                    <p class="mt-1 text-sm text-gray-700 dark:text-gray-300">
                        {{ $activity->description }}
                    </p>
                @endif

                <div class="mt-2">
                    @include('filament-activity-timeline::changes', [
                        'activity' => $activity,
                        'formatter' => $formatter,
                    ])
                </div>
            </div>
        </div>
    @empty
        <div class="flex flex-col items-center justify-center gap-2 py-10 text-center">
            <x-filament::icon icon="heroicon-o-archive-box" class="h-8 w-8 text-gray-300 dark:text-gray-600" />

            <p class="text-sm text-gray-500 dark:text-gray-400">
                {{ __('filament-activity-timeline::activity.empty.timeline') }}
            </p>
        </div>
    @endforelse

    @if ($total > $shown)
        <div class="mt-2 border-t border-gray-200 pt-4 text-center dark:border-white/10">
            <p class="text-xs text-gray-500 dark:text-gray-400">
                {{ __('filament-activity-timeline::activity.timeline.truncated', ['shown' => $shown, 'total' => $total]) }}
            </p>

            @if ($showAllUrl)
                <div class="mt-1">
                    <x-filament::link :href="$showAllUrl" size="sm">
                        {{ __('filament-activity-timeline::activity.timeline.show_all') }}
                    </x-filament::link>
                </div>
            @endif
        </div>
    @endif
</div>
