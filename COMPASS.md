# x-document Laravel Compass

**Completed milestone:** Laravel HTTP Response Adapter Contract

**Completed correction:** HTTP Conditional Entity-Tag Semantics Closure

**Completed milestone:** Laravel HTTP Adapter Host Integration Examples

**Completed correction:** Host Example Representation Metadata Correction

**Completed milestone:** Laravel 12 and Laravel 13 Compatibility Matrix

The package now converts an invariant-safe core browser host response into an
exact Laravel/Symfony HTTP response without rendering or business behavior.
Strong representation validators are emitted unchanged, while inbound
`If-None-Match` follows weak HTTP comparison with quote-aware list parsing.

Testbench now proves the complete host-facing pattern without adding production
routes, controllers, policies, or orchestration.
Styled examples use their matching styled format identifiers, with descriptor
consistency protected by a complete representation dataset.

The adapter's Laravel 12 and 13 declarations are now backed by isolated PHP 8.4
runtime evidence, explicit resolved-version checks, and clean-host package
discovery. The matrix does not change the adapter's runtime boundary.

## Recommended next slice

> **GNE → x-document Runtime Integration Vertical Slice**

Connect one accepted GNE subject through its resolved document, the x-document
browser host response, and this Laravel HTTP adapter in a real authenticated
host flow. Preserve all existing ownership boundaries and avoid broadening the
adapter package.
