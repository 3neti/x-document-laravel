# ADR-004: Keep Host Integration Examples Non-Authoritative

## Status

Accepted.

## Decision

Controller, resolver, authorization, allowlist, route, and exception-mapping
examples live only in documentation and Testbench fixtures. They are not part
of the production namespace or service provider.

## Rationale

Examples should make correct integration executable without turning the
adapter into application infrastructure. Lookup, authorization, representation
selection, disposition policy, and status mapping vary by host.

## Consequences

- The production API remains the response-factory contract and request context.
- Removing all examples does not affect package runtime behavior.
- Test-only routes prove integration without registering package routes.
- Hosts must implement their own resolver, policies, and exception handling.
