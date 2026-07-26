<?php

declare(strict_types=1);

use BokshornIt\FilamentActivityTimeline\Resources\ActivityResource;

return [

    /*
    |--------------------------------------------------------------------------
    | Resource
    |--------------------------------------------------------------------------
    |
    | The Filament resource listing every logged activity. Swap in your own
    | subclass to change the table, filters or infolist. Anything set on the
    | plugin (->navigationGroup(), ->navigationSort(), ...) wins over these.
    |
    */

    'resource' => ActivityResource::class,

    'navigation' => [
        'group' => null,
        'icon' => 'heroicon-o-archive-box',
        'sort' => 10,
        'enabled' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Translation namespaces
    |--------------------------------------------------------------------------
    |
    | Field and event labels live in your application, since only it knows what
    | its columns mean. The column "customer_id" is labelled from the key
    | "changes.customer_id", the event "invoice_sent" from
    | "activity_events.invoice_sent". Missing keys fall back to a headline
    | cased version of the key, so you can fill these in gradually.
    |
    */

    'field_label_namespace' => 'changes',

    'event_label_namespace' => 'activity_events',

    /*
    | Models that no Filament resource manages take their label from here,
    | keyed by the snake cased class name: "additional_cost" for an
    | AdditionalCost model. Resources always win over this.
    */

    'subject_label_namespace' => 'activity_subjects',

    /*
    |--------------------------------------------------------------------------
    | Ignored change keys
    |--------------------------------------------------------------------------
    |
    | Column keys that should never show up in a diff on any model. Derived
    | columns, caches, leftovers from an old schema.
    |
    */

    'ignored_keys' => [
        //
    ],

    /*
    |--------------------------------------------------------------------------
    | Value formatting
    |--------------------------------------------------------------------------
    |
    | How date and datetime casts are rendered inside a diff, and the string
    | shown when a value is null or empty.
    |
    */

    'date_format' => 'd.m.Y',

    'datetime_format' => 'd.m.Y H:i',

    'placeholder' => '-',

    /*
    |--------------------------------------------------------------------------
    | Timeline
    |--------------------------------------------------------------------------
    |
    | How many entries a record's timeline loads. Without a cap, a record with
    | years of history renders every single entry.
    |
    */

    'timeline_limit' => 50,

    /*
    |--------------------------------------------------------------------------
    | Causers
    |--------------------------------------------------------------------------
    |
    | An icon per causer class, plus the one used when an entry has no causer
    | at all (queue jobs, scheduled commands, webhooks). Names resolve through
    | the ProvidesActivityTitle contract and fall back to the model's "name".
    |
    */

    'causer_icons' => [
        //
    ],

    'default_causer_icon' => 'heroicon-m-user',

    'system_causer_icon' => 'heroicon-m-cog-6-tooth',

    /*
    |--------------------------------------------------------------------------
    | Custom events
    |--------------------------------------------------------------------------
    |
    | Besides created/updated/deleted/restored you can log your own events with
    | activity()->performedOn($invoice)->event('invoice_sent')->log('...') and
    | give them an icon and colour here. Unregistered events still render, just
    | with a neutral icon.
    |
    */

    'events' => [
        //
    ],

    /*
    |--------------------------------------------------------------------------
    | Restore
    |--------------------------------------------------------------------------
    |
    | Models whose logged "old" values may be written back onto the record.
    | Empty by default, since this is destructive and on bookkeeping records
    | usually wrong. The restore is logged as a new entry.
    |
    */

    'restorable' => [
        //
    ],

];
