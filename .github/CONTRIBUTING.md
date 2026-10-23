# Contributing

Use PHP 8.2 or newer with `intl`, `mbstring`, and SQLite PDO enabled. Install development dependencies with a current Composer 2, then run:

```bash
composer install
composer test -- --compact
composer test:lint
composer audit --no-dev
```

The standalone test suite uses an in-memory SQLite database and Testbench. Do not point it at an application or production database. CI tests Laravel 11/12, Filament 4/5, and Livewire 3/4 combinations; a default local install tests one compatible combination.

Keep public method signatures, the stored JSON envelope, default configuration, and both supported scope-configuration formats compatible. Add regression tests for changed behavior and document upgrade considerations in the README and changelog. Runtime dependencies should stay within the package's existing supported ranges unless a separate compatibility change is intended.

Report vulnerabilities privately using `SECURITY.md` instead of a public issue or pull request.
