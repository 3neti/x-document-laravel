<?php

namespace LBHurtado\XDocumentLaravel\Http;

use LBHurtado\XDocumentLaravel\Exceptions\InvalidEntityTag;

final readonly class HttpEntityTag
{
    private function __construct(public string $value) {}

    public static function fromCore(string $etag): self
    {
        if (preg_match('/^"[\x21\x23-\x7E]+"$/D', $etag) !== 1) {
            throw new InvalidEntityTag('The core ETag must be one strongly quoted HTTP entity tag.');
        }

        return new self($etag);
    }

    public function matchesIfNoneMatch(string $header): bool
    {
        $header = trim($header);
        if ($header === '') {
            throw new InvalidEntityTag('If-None-Match cannot be empty.');
        }
        if ($header === '*') {
            return true;
        }
        $candidates = preg_split('/\s*,\s*/', $header);
        if (! is_array($candidates)) {
            throw new InvalidEntityTag('If-None-Match is malformed.');
        }
        foreach ($candidates as $candidate) {
            if (preg_match('/^(?:W\/)?"[\x21\x23-\x7E]+"$/D', $candidate) !== 1) {
                throw new InvalidEntityTag('If-None-Match contains a malformed entity tag.');
            }
            if (! str_starts_with($candidate, 'W/') && hash_equals($this->value, $candidate)) {
                return true;
            }
        }

        return false;
    }
}
