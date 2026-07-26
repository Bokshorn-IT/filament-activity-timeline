# Contributing

Thanks for taking the time. Bug reports, ideas and pull requests are all welcome.

## Reporting a bug

Please include the Filament, Laravel and PHP versions you are on, and the smallest piece of code that reproduces the problem. A failing test says more than a description.

Security problems do not belong in the issue tracker. See [SECURITY.md](../SECURITY.md).

## Pull requests

Run the suite and the formatter before pushing:

```bash
composer test
composer lint
```

A few things that will save a round trip:

- Add a test that fails without your change. If it passes either way, it is not testing the change.
- Keep the diff to one concern.
- Anything user-facing needs a line in [CHANGELOG.md](../CHANGELOG.md) and, if it is configurable, a mention in the README.
- New strings go into both `resources/lang/en` and `resources/lang/de`.

## Running the tests

The suite runs on SQLite in memory by default:

```bash
composer test
```

CI also runs it against MySQL and PostgreSQL. To do the same locally, point `DB_CONNECTION` at a running server:

```bash
DB_CONNECTION=mysql DB_DATABASE=testing DB_USERNAME=root DB_PASSWORD=password vendor/bin/pest
```

Models are strict in the test harness (`Model::shouldBeStrict()`), so a lazy load or a missing attribute fails the suite rather than passing quietly. That is deliberate: host applications commonly enable it.
