# Architecture

`3neti/x-document-laravel` is an HTTP delivery adapter downstream of
`3neti/x-document`.

```text
GNE or another producer
        ↓
x-document resolved projection
        ↓
BrowserHostResponse
        ↓
DocumentHttpResponseFactory
        ↓
Symfony Response
        ↓
host-owned Laravel route
```

## Ownership

`3neti/x-document` owns representation construction, canonical bytes, media
type, filename, disposition, checksum, byte length, and authoritative ETag.

This package owns only:

- safe mapping of those facts into HTTP headers and a response body;
- GET and HEAD delivery semantics;
- HTTP weak `If-None-Match` evaluation against the strong core validator;
- safe `Content-Disposition` serialization;
- defensive integrity checks at the integration boundary;
- Laravel container registration.

The host application owns routing, authentication, authorization, caching
infrastructure, exception presentation, and acquisition of the core response.

## Dependency direction

The package depends on the portable `3neti/x-document` contract. Core
`3neti/x-document` does not depend on Laravel or this package.

There are no dependencies on repositories, business artifacts, compilation
subjects, lifecycle logic, GNE, Eloquent, frontend frameworks, settlement, or
workflow.

## Exact-byte invariant

The response body is assigned directly from
`BrowserHostResponse::output->inlineContent`. It is never decoded, re-encoded,
templated, streamed from a path, or rendered. Before delivery, the adapter
checks:

```text
strlen(body) == declared byte length
sha256(body) == declared checksum
core ETag is a valid strongly quoted entity tag
```

HEAD and 304 responses omit body bytes by HTTP semantics; HEAD retains the
length of the corresponding GET representation.

## Conditional requests

The minimal request context carries only method and `If-None-Match`. Core still
supplies a strong validator, but HTTP requires weak comparison for
`If-None-Match`: weakness is ignored and the quoted opaque values are compared
exactly. A focused parser recognizes separators only outside quotes, so a comma
inside an opaque tag is preserved. Standalone wildcard and optional whitespace
are supported. Wildcard combinations, empty members, controls, and malformed
tags fail explicitly instead of being silently ignored.

## Security posture

- CR/LF header injection is rejected.
- Media types are constrained to a safe HTTP shape.
- Filenames cannot contain path separators or local-path forms.
- Unicode is exposed through RFC 5987-style `filename*`, alongside a safe
  quoted ASCII fallback.
- The package emits `nosniff`.
- No credentials, sessions, users, cookies, or authorization state enter the
  adapter model.

## Framework integration

The service provider binds one singleton contract:

```text
DocumentHttpResponseFactory
    → LaravelDocumentHttpResponseFactory
```

Package discovery loads the provider. No application endpoints or middleware
are registered.
