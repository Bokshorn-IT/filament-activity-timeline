@php
    use BokshornIt\FilamentActivityTimeline\Support\ChangeFormatter;

    /** @var \Spatie\Activitylog\Models\Activity $activity */
    $activity = $activity ?? $getRecord();

    // The timeline passes its own formatter in so the resolved foreign-key
    // titles are cached across every entry rather than per entry. On its own
    // (the resource infolist renders one activity) a fresh one is fine.
    $rows = ($formatter ?? ChangeFormatter::make())->rows($activity);
@endphp

@if ($rows->isNotEmpty())
    <dl class="divide-y divide-gray-100 overflow-hidden rounded-lg bg-gray-50 px-3 ring-1 ring-gray-950/5 dark:divide-white/5 dark:bg-white/5 dark:ring-white/10">
        @foreach ($rows as $row)
            <div class="grid grid-cols-3 gap-x-3 gap-y-1 py-2 text-sm">
                <dt class="truncate font-medium text-gray-700 dark:text-gray-300" title="{{ $row['label'] }}">
                    {{ $row['label'] }}
                </dt>

                <dd class="col-span-2 flex flex-wrap items-center gap-x-2 gap-y-1">
                    @if ($row['changed'])
                        <span class="text-gray-400 line-through dark:text-gray-500">{{ $row['old'] }}</span>

                        <x-filament::icon
                            icon="heroicon-m-arrow-right"
                            class="h-4 w-4 flex-none text-gray-400 dark:text-gray-500"
                        />
                    @endif

                    <span class="font-medium text-gray-950 dark:text-white">{{ $row['new'] }}</span>
                </dd>
            </div>
        @endforeach
    </dl>
@else
    <p class="text-sm text-gray-500 dark:text-gray-400">
        {{ __('filament-activity-timeline::activity.no_changes') }}
    </p>
@endif
