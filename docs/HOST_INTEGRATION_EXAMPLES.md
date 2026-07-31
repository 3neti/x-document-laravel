# Laravel Host Integration Examples

These examples show how a Laravel application can deliver an already-produced
`BrowserHostResponse`. They are executable in
`tests/Fixtures/HostApplication` and are not package runtime infrastructure.

## Integration boundary

```text
host route/controller
        ↓
host lookup and authorization
        ↓
host representation and disposition policy
        ↓
x-document BrowserHostResponse
        ↓
DocumentHttpRequestContext
        ↓
DocumentHttpResponseFactory
        ↓
Symfony Response
```

The host owns every step above `BrowserHostResponse`. This package owns only
safe, byte-exact HTTP delivery.

## Minimal direct usage

```php
$factory = new LaravelDocumentHttpResponseFactory();

$response = $factory->make(
    response: $browserHostResponse,
    request: DocumentHttpRequestContext::get(),
);
```

This form is useful when a host does not need container resolution. The body is
already canonical JSON or complete HTML; the factory must not parse or render
it.

## Controller integration

The executable example controller is intentionally under the test fixture
namespace:

```php
final readonly class ExampleShowDocumentController
{
    public function __construct(
        private DocumentHttpResponseFactory $responses,
        private HostDocumentRepresentationResolver $documents,
        private HostDocumentAuthorizer $authorization,
    ) {}

    public function __invoke(Request $request, string $documentId): Response
    {
        $this->authorization->assertMayView($request->user(), $documentId);
        $representation = ExampleRepresentation::fromRequest(
            $request->query(
                'representation',
                ExampleRepresentation::StyledCompositionHtml->value,
            ),
        );
        $disposition = $request->boolean('download')
            ? BrowserContentDisposition::Attachment
            : BrowserContentDisposition::Inline;
        $hostResponse = $this->documents->resolve(
            $documentId,
            $representation,
            $disposition,
        );

        return $this->responses->make(
            $hostResponse,
            DocumentHttpRequestContext::fromLaravelRequest($request),
        );
    }
}
```

`HostDocumentRepresentationResolver` and `HostDocumentAuthorizer` are
application-owned example interfaces. The package does not provide them.

## Host-owned orchestration

A real host resolver may:

1. load an application record or accepted `ResolvedDocument`;
2. enforce tenant and document access;
3. choose an allowlisted representation;
4. construct the appropriate x-document host request;
5. call the x-document host resolver once;
6. return its `BrowserHostResponse` unchanged.

It must not move those responsibilities into `DocumentHttpResponseFactory`.
The deterministic fake used by the tests has no database, network, GNE, or
business interpretation.

The example fixture bodies are deterministic stand-ins for already-produced
x-document representations. They are not canonical compatibility fixtures for
the x-document representation grammars. Real hosts should obtain
`BrowserHostResponse` from x-document rather than construct replacement
representation grammars from these examples.

## Authorization placement

Authorize before representation resolution and delivery. Authentication,
authorization policies, tenant isolation, rate limiting, and audit logging are
host responsibilities. Only method and `If-None-Match` cross from the Laravel
request into `DocumentHttpRequestContext`; users, cookies, sessions, bearer
tokens, route parameters, and arbitrary headers do not.

## Representation allowlist

The test host uses `ExampleRepresentation` as an application-level allowlist:

- `browser-json`;
- `browser-html`;
- `browser-html-styled`;
- `browser-composition-html-styled`.

An unknown value is rejected before the resolver runs. Production hosts may
choose a different allowlist, but must not pass arbitrary strings into
x-document or ask the response factory to choose.

The test fixture keeps representation metadata internally consistent:

| Representation | Format | Media type | Filename suffix |
|---|---|---|---|
| `browser-json` | `browser/1.0` | `application/vnd.3neti.x-document.browser+json` | `.browser.json` |
| `browser-html` | `browser-html/1.0` | `text/html; charset=utf-8` | `.html` |
| `browser-html-styled` | `browser-html-styled/1.0` | `text/html; charset=utf-8` | `.styled.html` |
| `browser-composition-html-styled` | `browser-composition-html-styled/1.0` | `text/html; charset=utf-8` | `.composition.styled.html` |

## Inline and attachment delivery

The example host interprets `download=1` as its own attachment policy. The
resolver places that decision in `BrowserHostResponse`. The adapter then emits
safe `Content-Disposition` syntax, including an ASCII fallback and UTF-8
`filename*` for `résumé-document...`. No filesystem download is involved.

## GET, HEAD, and conditional requests

- GET resolves once and returns exact bytes.
- HEAD resolves once, returns no body, and retains the GET byte length.
- Matching strong or weak `If-None-Match` returns bodyless 304.
- Conditional HEAD also resolves once and retains the authoritative ETag.
- Non-matching requests preserve ordinary GET or HEAD behavior.

Current cache headers are:

```text
Cache-Control: private, no-cache
ETag: "<authoritative-core-validator>"
```

`no-cache` permits storage but requires validation before reuse.

## JSON and HTML representations

Canonical JSON is returned as existing bytes, never through
`response()->json()`. Semantic, styled, and styled-composition HTML are
returned as complete existing HTML, never wrapped in Blade or another
application layout. The composition fixture carries an inert interaction
declaration only; no action is executed.

## Exception handling

Status selection is host policy. Example mappings might be:

- unknown document → 404;
- unauthorized document → 403;
- disallowed representation → 400 or 404;
- malformed `If-None-Match` → host-chosen 400;
- invalid core response → implementation failure or host-chosen 500.

Centralized Laravel exception handling is preferable for a real application.
The test host includes a route-local mapping only to prove the package does not
own that mapping.

## Testing a host integration

Orchestra Testbench registers routes and host bindings inside tests only. Tests
assert authorization ordering, one resolver invocation per request, exact
bytes, headers, conditional delivery, representation allowlisting, and
host-owned exception mapping.

## Anti-patterns

Do not re-encode canonical JSON:

```php
return response()->json(json_decode($hostResponse->body, true));
```

Do not wrap complete HTML:

```php
return view('document', ['html' => $hostResponse->body]);
```

Do not replace inline bytes with a filesystem download:

```php
return response()->download($filesystemPath);
```

Do not pass a resolved document, repository record, or arbitrary representation
name to the response factory. Do not add package-owned routes or policies.
