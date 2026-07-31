# Decision Register

| Decision | Status | Consequence |
|---|---|---|
| [ADR-001](docs/adr/001-separate-laravel-delivery-package.md): Laravel delivery is a separate package | Accepted | Core remains framework-independent. |
| [ADR-002](docs/adr/002-preserve-authoritative-representation-bytes.md): preserve authoritative bytes | Accepted | The adapter never renders or re-encodes output. |
| [ADR-003](docs/adr/003-use-authoritative-strong-etags.md): emit authoritative strong ETags and weakly evaluate `If-None-Match` | Amended | Conditional delivery follows HTTP semantics without inventing a second identity. |

## Deferred decisions

- range requests;
- download acceleration and streamed delivery;
- configurable cache policy;
- weak emitted ETags;
- controller and route conveniences;
- content-security policy integration;
- Laravel 14 compatibility certification.
