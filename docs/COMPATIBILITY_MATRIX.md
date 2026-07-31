# Compatibility Matrix

Compatibility is certified through isolated dependency resolution and runtime
execution, not inferred from Composer constraints.

| PHP | Laravel | Testbench | Symfony HttpFoundation | Status |
|---|---|---|---|---|
| 8.4 | 12 | 10 | 7 | Runtime-tested locally and configured in CI |
| 8.4 | 13 | 11 | 8 | Runtime-tested locally and configured in CI |

The exact patch versions are printed by every run. Illuminate component
requirements are satisfied by `laravel/framework`, which replaces those
component packages in the isolated test environment.

PHP 8.4 is the only certified PHP line because `3neti/x-document` requires
`^8.4`. Adding older PHP cells would conflict with the core dependency rather
than add meaningful adapter support. Laravel 14 and future framework majors are
not declared or tested.

## Local reproduction

Check out `x-document` beside this repository, then run:

```bash
composer compatibility:laravel-12
composer compatibility:laravel-13
```

Set `X_DOCUMENT_PATH` when the core checkout is elsewhere. Each command:

1. copies this package into a unique temporary workspace;
2. removes `vendor`, VCS data, caches, and any local lock file;
3. constrains the selected Laravel and Testbench majors;
4. performs a fresh dependency resolution;
5. asserts the actual resolved versions;
6. composes a minimal clean host and verifies metadata-driven package
   discovery plus response-factory resolution;
7. runs the complete Pest suite, PHPStan, and Pint;
8. deletes the temporary workspace.

The active package worktree is never rewritten by a compatibility run.

## CI

`.github/workflows/compatibility.yml` runs both cells independently on PHP 8.4.
Because `3neti/x-document` is a separate private repository, CI requires an
`X_DOCUMENT_TOKEN` secret with read access. A configured workflow is
**CI-configured**; only an observed successful GitHub Actions run is
**CI-tested**. Local results are reported separately.

## Lock-file policy

This is a library package, so `composer.lock` is ignored and not committed.
The local development install may currently resolve Laravel 13, but that state
does not define the supported matrix. Each compatibility cell creates its own
temporary lock through genuine resolution and then discards it.

## Support language

- **Dependency-compatible:** Composer can resolve the declared constraints.
- **Runtime-tested:** a complete local cell passed for the exact combination.
- **CI-configured:** the combination exists in the committed workflow.
- **CI-tested:** an observed remote workflow run passed.
- **Not tested:** no complete cell has run; constraints alone are insufficient.
