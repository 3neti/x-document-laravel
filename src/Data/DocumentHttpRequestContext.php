<?php

namespace LBHurtado\XDocumentLaravel\Data;

use Illuminate\Http\Request;

final readonly class DocumentHttpRequestContext
{
    public function __construct(
        public string $method,
        public ?string $ifNoneMatch,
    ) {
        if (! in_array($method, ['GET', 'HEAD'], true)) {
            throw new \InvalidArgumentException('Document HTTP request method must be GET or HEAD.');
        }
    }

    public static function fromLaravelRequest(Request $request): self
    {
        $header = $request->headers->get('If-None-Match');

        return new self(
            strtoupper($request->getMethod()),
            is_string($header) ? $header : null,
        );
    }

    public static function get(?string $ifNoneMatch = null): self
    {
        return new self('GET', $ifNoneMatch);
    }

    public static function head(?string $ifNoneMatch = null): self
    {
        return new self('HEAD', $ifNoneMatch);
    }
}
