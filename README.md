# 3neti/x-document-laravel

Laravel HTTP delivery for portable [`3neti/x-document`](../x-document) browser host responses.

This package has one responsibility:

```text
x-document BrowserHostResponse
        ↓
Laravel HTTP response adapter
        ↓
Symfony HTTP response
```

It preserves the bytes and representation facts already established by
`3neti/x-document`. It does not render, encode, resolve, authorize, persist, or
execute documents.

## Requirements

- PHP 8.4
- Laravel 12 or 13
- `3neti/x-document`

## Installation

```bash
composer require 3neti/x-document-laravel
```

Laravel package discovery registers
`LBHurtado\XDocumentLaravel\XDocumentLaravelServiceProvider`, which binds
`DocumentHttpResponseFactory` as a singleton.

## Usage

```php
use Illuminate\Http\Request;
use LBHurtado\XDocument\Browser\Host\BrowserHostResponse;
use LBHurtado\XDocumentLaravel\Contracts\DocumentHttpResponseFactory;
use LBHurtado\XDocumentLaravel\Data\DocumentHttpRequestContext;
use Symfony\Component\HttpFoundation\Response;

final class ShowDocument
{
    public function __construct(
        private DocumentHttpResponseFactory $responses,
    ) {}

    public function __invoke(
        Request $request,
        BrowserHostResponse $document,
    ): Response {
        return $this->responses->make(
            $document,
            DocumentHttpRequestContext::fromLaravelRequest($request),
        );
    }
}
```

The host application owns routes, authentication, authorization, and obtaining
the `BrowserHostResponse`.

## HTTP behavior

- GET returns the exact inline bytes.
- HEAD returns no body and preserves the GET representation length.
- `If-None-Match` accepts `*` and comma-separated entity tags.
- A matching strong ETag returns `304 Not Modified`.
- Content type, filename, disposition, checksum, length, and ETag come from the
  core response and are checked before delivery.
- Cache policy is `private, no-cache`.
- `X-Content-Type-Options: nosniff` is always emitted.
- Unicode filenames receive a safe ASCII fallback plus `filename*=UTF-8''...`.
- Absolute paths, path separators, NUL, CR, and LF are rejected in filenames.

The package deliberately registers no routes, controllers, views, middleware,
migrations, commands, queues, or storage.

## Quality gates

```bash
composer validate --strict
composer test
vendor/bin/pint --dirty --format agent
vendor/bin/phpstan analyse --no-progress
```

See [ARCHITECTURE.md](ARCHITECTURE.md), [GRAMMAR.md](GRAMMAR.md), and
[IMPLEMENTATION_STATUS.md](IMPLEMENTATION_STATUS.md) for the durable boundary.
