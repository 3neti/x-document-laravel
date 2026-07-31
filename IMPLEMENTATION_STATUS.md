# Implementation Status

## Implemented

- Composer package `3neti/x-document-laravel`.
- Namespace `LBHurtado\XDocumentLaravel`.
- Laravel 12/13 dependency constraints.
- isolated PHP 8.4 / Laravel 12 and PHP 8.4 / Laravel 13 compatibility cells.
- resolved-version assertions for Laravel, Testbench, Symfony HttpFoundation,
  PHP, and x-document.
- clean-host Composer package-discovery and container-binding verification.
- GitHub Actions compatibility matrix plus reproducible local Composer scripts.
- Laravel package discovery and singleton response-factory binding.
- Exact inline-byte GET responses.
- bodyless HEAD responses with representation length.
- core media type, disposition, filename, checksum, byte length, and ETag
  preservation.
- weak `If-None-Match` comparison against strong core ETags.
- quote-aware entity-tag lists and standalone wildcard evaluation.
- `304 Not Modified`.
- deterministic, Unicode-aware, injection-safe `Content-Disposition`.
- private/no-cache and nosniff response policy.
- unit, feature, integration, and architecture coverage.
- executable Testbench host examples for direct and controller delivery,
  authorization ordering, allowlisting, GET/HEAD, conditionals, dispositions,
  JSON, semantic HTML, styled HTML, and styled composition HTML.
- dedicated host integration guide and example-boundary ADR.
- dataset-protected example descriptor mappings whose representation, format,
  media type, and filename suffix identify the same output.

## Partial

- HTTP caching is deliberately fixed rather than host-configurable.
- Conditional requests support `If-None-Match`; date validators and byte ranges
  are not implemented.
- CI execution requires checkout access to the private `3neti/x-document`
  repository through `X_DOCUMENT_TOKEN`.

## Deferred

- routes, controllers, middleware, and authorization;
- views or frontend integration;
- streaming, range responses, output persistence, queues, and remote delivery;
- rendering or representation compilation;
- repository, lifecycle, workflow, settlement, and business behavior.
- PHP 8.3 certification while `3neti/x-document` requires PHP 8.4;
- Laravel 14 certification.

This package is an adapter, not an application.

Example controllers, routes, resolvers, authorizers, and policies are test-only
fixtures and are not production package capabilities.
Their deterministic bodies illustrate transport integration and are not
canonical x-document compatibility fixtures.
