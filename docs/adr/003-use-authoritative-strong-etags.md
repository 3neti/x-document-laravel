# ADR-003: Use Authoritative Strong ETags

## Status

Accepted.

## Decision

The adapter forwards the strongly quoted ETag supplied by core and uses strong
comparison for `If-None-Match`.

## Rationale

Core already derives representation identity from authoritative bytes. A
Laravel adapter must not invent another validator or weaken byte identity.

## Consequences

- Exact strong matches and `*` return 304.
- Weak candidates do not match.
- Comma-separated candidates are evaluated deterministically.
- Malformed entity-tag syntax fails explicitly.
- Date validators and weak comparison remain deferred.
