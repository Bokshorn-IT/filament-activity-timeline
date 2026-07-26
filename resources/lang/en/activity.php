<?php

declare(strict_types=1);

return [

    'model' => [
        'singular' => 'Activity',
        'plural' => 'Activities',
    ],

    'sections' => [
        'changes' => 'Changes',
    ],

    'fields' => [
        'event' => 'Event',
        'created_at' => 'Timestamp',
        'subject' => 'Record',
        'subject_type' => 'Record type',
        'causer' => 'User',
        'log_name' => 'Log',
        'description' => 'Description',
        'date_from' => 'From',
        'date_until' => 'Until',
    ],

    'events' => [
        'created' => 'Created',
        'updated' => 'Updated',
        'deleted' => 'Deleted',
        'restored' => 'Restored',
    ],

    'boolean' => [
        'true' => 'yes',
        'false' => 'no',
    ],

    'causer' => [
        'system' => 'System',
    ],

    'indicators' => [
        'from' => 'From',
        'until' => 'Until',
    ],

    'empty' => [
        'heading' => 'No activity',
        'description' => 'As soon as records are created or changed, they will appear here.',
        'timeline' => 'Nothing has been logged for this record yet.',
    ],

    'no_changes' => 'No field changes recorded.',

    'action' => [
        'label' => 'History',
        'heading' => 'History',
        'close' => 'Close',
    ],

    'timeline' => [
        'truncated' => ':shown of :total entries',
        'show_all' => 'Show all',
    ],

    'actions' => [
        'open_subject' => 'Open record',
    ],

    'restore' => [
        'label' => 'Restore',
        'heading' => 'Restore version',
        'description' => 'The record will be reset to the previous values stored in this entry. This creates a new log entry.',
        'submit' => 'Restore',
        'failed_title' => 'Restore not possible',
        'failed_body' => 'The associated record is no longer available.',
        'unchanged_title' => 'Nothing to restore',
        'unchanged_body' => 'The record already holds the previous values.',
        'restored_title' => 'Record restored',
        'restored_body' => 'The previous values have been applied.',
    ],

    'formats' => [
        'datetime_full' => 'd.m.Y H:i:s',
    ],

];
