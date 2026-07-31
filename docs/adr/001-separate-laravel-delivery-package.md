# ADR-001: Keep Laravel HTTP Delivery in a Separate Package

## Status

Accepted.

## Decision

Laravel HTTP delivery lives in `3neti/x-document-laravel`, downstream of
`3neti/x-document`.

## Rationale

Core document projections are portable and useful without Laravel. HTTP
response construction introduces framework contracts, request semantics, and
header security concerns that do not belong in core.

## Consequences

- Core remains framework-independent.
- Host applications opt into Laravel delivery.
- This package cannot interpret repository or business meaning.
- The integration boundary is the immutable `BrowserHostResponse`.
