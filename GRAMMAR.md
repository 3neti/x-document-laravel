# Grammar

This grammar describes HTTP delivery concepts only.

## Document HTTP Response Factory

The public boundary that maps one invariant-safe `BrowserHostResponse` and an
optional `DocumentHttpRequestContext` to a Symfony `Response`.

## Document HTTP Request Context

An immutable request projection containing:

- method: `GET` or `HEAD`;
- optional `If-None-Match` header.

It deliberately excludes users, sessions, cookies, authorization, routes, and
the Laravel request object itself.

## Entity Tag

A strongly quoted opaque HTTP validator. The authoritative value is supplied by
`3neti/x-document`; this package validates and forwards it without deriving a
replacement.

## Conditional Match

A weak HTTP comparison between the authoritative strong entity tag and one
member of `If-None-Match`, or a standalone wildcard match. Weakness prefixes
are ignored; opaque values remain byte-sensitive. A match yields
`304 Not Modified`.

## If-None-Match

Either `*` alone or an ordered comma-separated list of strong or weak quoted
entity tags. Optional spaces or tabs may surround separators. Commas inside a
quoted opaque value are data, not separators. Empty members, combined
wildcards, controls, unquoted values, and malformed quotes are invalid.

## Representation Headers

For a successful GET or HEAD:

- `Content-Type`: core media type;
- `Content-Disposition`: core disposition and safely encoded filename;
- `Content-Length`: exact core body byte length;
- `ETag`: authoritative core ETag;
- `Cache-Control`: `private, no-cache`;
- `X-Content-Type-Options`: `nosniff`.

For 304, representation-body headers are omitted while ETag and cache/security
policy remain.

## Source Filename

The core output filename after validating it as a filename rather than a path.
It cannot be empty, `.` or `..`, contain NUL/CR/LF, or contain `/` or `\`.

## Exact Bytes

The inline content supplied by core. The adapter performs no semantic or
encoding transformation.

## Delivery Error

An explicit exception caused by unsafe HTTP input or an inconsistent core
response. Unexpected implementation failures also propagate; they are not
converted into ordinary document output.

## Host Document Resolver

An example-only application boundary that produces `BrowserHostResponse` after
host-owned lookup, authorization, representation selection, and x-document
resolution. It is not a production package contract.

## Host Representation Allowlist

An application-owned closed set of representations that may be requested from
x-document. The HTTP response factory never selects representations.

## Host Exception Translation

Application policy that maps lookup, authorization, request, integration, or
implementation failures to HTTP responses. This package defines integration
exceptions but does not assign status codes.
