# Testing

DATETIME of last agent review: 23 Dec 2025 11:30 (Europe/London)

## Purpose

Custom PHP test framework for unit and integration tests; runs on PHP 7.0+.

## Test Commands

```bash
cd /home/pinescore/public_html/tests && php run-tests.php
```

Execution time: ~10-30 seconds depending on integration tests.

## Key Test Files

- `tests/run-tests.php` - auto-discovery test runner
- `tests/bootstrap.php` - test environment setup
- `tests/unit/` - isolated component tests
- `tests/integration/controllers/` - controller integration tests

## Coverage Scope

- Unit tests for models in `tests/unit/models/`
- Integration tests for controllers: ApiNightly, Events, SearchNodes, Tools
- Custom TestCase class with standard assertions

## Agent Testing Protocol

**MANDATORY:** Run `php tests/run-tests.php` after every feature or change.
- Target execution: <30s
- On failure: fix immediately, do not defer

## Notes

- Tests require PHP 7.0+ (app runs on PHP 5.6)
- Test methods must start with `test` for auto-discovery
