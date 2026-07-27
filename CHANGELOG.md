# Changelog

All notable changes to `filament-activity-timeline` will be documented in this file.

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
