<?php

namespace LBHurtado\XDocumentLaravel\Http;

use LBHurtado\XDocumentLaravel\Exceptions\InvalidEntityTag;

final readonly class HttpEntityTag
{
    private function __construct(
        public string $value,
        private string $opaqueValue,
        private bool $weak,
    ) {}

    public static function fromCore(string $etag): self
    {
        if (preg_match('/^"[\x21\x23-\x7E]+"$/D', $etag) !== 1) {
            throw new InvalidEntityTag('The core ETag must be one strongly quoted HTTP entity tag.');
        }

        return new self($etag, substr($etag, 1, -1), false);
    }

    public static function fromHeaderValue(string $etag): self
    {
        $matches = [];
        if (preg_match('/^(W\/)?"([\x21\x23-\x7E\x80-\xFF]*)"$/D', $etag, $matches) !== 1) {
            throw new InvalidEntityTag('If-None-Match contains a malformed entity tag.');
        }

        return new self($etag, $matches[2], $matches[1] === 'W/');
    }

    public function opaqueValue(): string
    {
        return $this->opaqueValue;
    }

    public function isWeak(): bool
    {
        return $this->weak;
    }

    public function weaklyEquals(self $other): bool
    {
        return hash_equals($this->opaqueValue, $other->opaqueValue);
    }
}
