# ADR-002: Preserve Authoritative Representation Bytes

## Status

Accepted.

## Decision

The adapter returns the inline bytes supplied by `BrowserHostResponse` exactly.
It does not render, decode, normalize, or re-encode them.

## Rationale

The producing driver already established the representation checksum, byte
length, media type, and filename. Reprocessing the body would create a second
compiler and could invalidate identity and cache semantics.

## Consequences

- GET bytes are byte-for-byte identical to core output.
- HEAD omits the body but reports the GET representation length.
- Integrity disagreement is an exception, not silent repair.
- Streaming and file-path delivery remain deferred.
