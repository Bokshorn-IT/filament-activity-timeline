# Changelog

All notable changes to `filament-activity-timeline` will be documented in this file.

## v1.1.0 - 2026-07-29

Support for `spatie/laravel-activitylog` v5 alongside v4 ([#1](https://github.com/Bokshorn-IT/filament-activity-timeline/issues/1)).

v5 moved the before/after values out of `properties` into their own `attribute_changes` column. The plugin now reads whichever the installed version writes, so timelines, diffs and restore behave the same on both. Nothing to change in your app: the constraint widens to `^4.12|^5.0` and v4 stays supported.

- Change pairs read through `Support\ActivityChanges`, covering both storage shapes - and a custom activity model that forgot the cast
- CI runs the full suite against activitylog v4 and v5

## v1.0.1 - 2026-07-27

No changes to the shipped code.

- Demo application under `workbench/`, runnable with `composer serve`
- README: screenshots, a compatibility table and a section on running the demo

## v1.0.0 - 2026-07-26

Initial release.

- Record timeline slide-over with `withRelations()` and a configurable entry limit
- Activity resource with event, record type, causer and date-range filters
- Change formatter resolving enum casts, date casts, foreign keys, booleans and morph columns to readable values
- `ProvidesActivityTitle` contract naming a model as subject, causer and foreign-key target
- Custom event registry with per-event icon and colour
- Typed causer resolution, including a System presentation for unattended writes
- Opt-in restore, limited to models named on `->restorable()`
- Single `->modifyQueryUsing()` scope applied to both the resource and every timeline
- Subject labels for models no Filament resource manages, via `->subjectLabelNamespace()`
- German and English translations
- Tested against SQLite, MySQL 8 and PostgreSQL 17, on PHP 8.3 to 8.5
