<?php

declare(strict_types=1);

return [

    'model' => [
        'singular' => 'Aktivität',
        'plural' => 'Aktivitäten',
    ],

    'sections' => [
        'changes' => 'Änderungen',
    ],

    'fields' => [
        'event' => 'Ereignis',
        'created_at' => 'Zeitpunkt',
        'subject' => 'Datensatz',
        'subject_type' => 'Datensatztyp',
        'causer' => 'Benutzer',
        'log_name' => 'Protokoll',
        'description' => 'Beschreibung',
        'date_from' => 'Von',
        'date_until' => 'Bis',
    ],

    'events' => [
        'created' => 'Erstellt',
        'updated' => 'Aktualisiert',
        'deleted' => 'Gelöscht',
        'restored' => 'Wiederhergestellt',
    ],

    'boolean' => [
        'true' => 'ja',
        'false' => 'nein',
    ],

    'causer' => [
        'system' => 'System',
    ],

    'indicators' => [
        'from' => 'Ab',
        'until' => 'Bis',
    ],

    'empty' => [
        'heading' => 'Keine Aktivitäten',
        'description' => 'Sobald Datensätze erstellt oder geändert werden, erscheinen sie hier.',
        'timeline' => 'Für diesen Datensatz wurde noch nichts protokolliert.',
    ],

    'no_changes' => 'Keine Feldänderungen protokolliert.',

    'action' => [
        'label' => 'Verlauf',
        'heading' => 'Verlauf',
        'close' => 'Schließen',
    ],

    'timeline' => [
        'truncated' => ':shown von :total Einträgen',
        'show_all' => 'Alle anzeigen',
    ],

    'actions' => [
        'open_subject' => 'Zum Datensatz',
    ],

    'restore' => [
        'label' => 'Wiederherstellen',
        'heading' => 'Version wiederherstellen',
        'description' => 'Der Datensatz wird auf die in diesem Eintrag gespeicherten vorherigen Werte zurückgesetzt. Das erzeugt einen neuen Protokolleintrag.',
        'submit' => 'Wiederherstellen',
        'failed_title' => 'Wiederherstellen nicht möglich',
        'failed_body' => 'Der zugehörige Datensatz ist nicht mehr verfügbar.',
        'unchanged_title' => 'Nichts wiederherzustellen',
        'unchanged_body' => 'Der Datensatz trägt die vorherigen Werte bereits.',
        'restored_title' => 'Datensatz wiederhergestellt',
        'restored_body' => 'Die vorherigen Werte wurden übernommen.',
    ],

    'formats' => [
        'datetime_full' => 'd.m.Y H:i:s',
    ],

];
