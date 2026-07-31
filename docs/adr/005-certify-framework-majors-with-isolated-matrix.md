# ADR-005: Certify framework majors with an isolated matrix

## Status

Accepted.

## Context

The package declares Laravel 12 and 13 compatibility, while an ordinary local
install resolves only one framework major. A library lock file would make the
other major invisible and could create a false compatibility claim.

## Decision

Certify PHP 8.4 with Laravel 12/Testbench 10 and Laravel 13/Testbench 11 in
separate disposable, lock-free environments. Assert actual dependency versions,
compose a minimal host to verify metadata-driven package discovery, and execute
the full tests, PHPStan, and Pint in each cell. Mirror those cells in CI.

The library continues to ignore `composer.lock`. CI needs explicit read access
to the separate private `3neti/x-document` repository.

## Consequences

Compatibility claims become reproducible evidence and cannot silently resolve
the wrong Laravel major. The matrix is slower than a single locked install, but
it remains development-only and does not expand production code or dependencies.
PHP versions below 8.4 and Laravel 14 remain uncertified until the dependency
contract and a real matrix justify them.
