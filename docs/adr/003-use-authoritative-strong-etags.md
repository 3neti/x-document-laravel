# ADR-003: Use Authoritative Strong ETags

## Status

Accepted.

## Decision

The adapter forwards the strongly quoted ETag supplied by core. It evaluates
inbound `If-None-Match` using HTTP weak comparison.

## Rationale

Core already derives representation identity from authoritative bytes. A
Laravel adapter must not invent another validator or weaken emitted byte
identity. HTTP nevertheless defines weak comparison for `If-None-Match`, so
candidate weakness is ignored while opaque values remain exact.

## Consequences

- Exact strong and weak candidate matches return 304.
- The emitted core ETag remains strong and unchanged.
- A standalone `*` matches the current representation.
- Quote-aware parsing preserves commas inside opaque values.
- Wildcard combinations and malformed entity-tag lists fail explicitly.
- Date validators, ranges, and weak emitted ETags remain deferred.
