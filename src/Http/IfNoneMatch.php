<?php

namespace LBHurtado\XDocumentLaravel\Http;

use LBHurtado\XDocumentLaravel\Exceptions\InvalidEntityTag;

final readonly class IfNoneMatch
{
    /**
     * @param  list<HttpEntityTag>  $entityTags
     */
    private function __construct(
        public bool $wildcard,
        public array $entityTags,
    ) {}

    public static function parse(string $value): self
    {
        self::rejectControlCharacters($value);
        $value = trim($value, " \t");
        if ($value === '') {
            throw new InvalidEntityTag('If-None-Match cannot be empty.');
        }
        if ($value === '*') {
            return new self(true, []);
        }

        $entityTags = [];
        $offset = 0;
        $length = strlen($value);
        while ($offset < $length) {
            self::skipOptionalWhitespace($value, $offset, $length);
            $start = $offset;
            if (substr($value, $offset, 2) === 'W/') {
                $offset += 2;
            }
            if ($offset >= $length || $value[$offset] !== '"') {
                throw new InvalidEntityTag('If-None-Match contains a malformed entity tag.');
            }

            $offset++;
            while ($offset < $length && $value[$offset] !== '"') {
                if (! self::isOpaqueTagCharacter(ord($value[$offset]))) {
                    throw new InvalidEntityTag('If-None-Match contains an invalid opaque tag character.');
                }
                $offset++;
            }
            if ($offset >= $length) {
                throw new InvalidEntityTag('If-None-Match contains an unclosed entity tag.');
            }

            $offset++;
            $entityTags[] = HttpEntityTag::fromHeaderValue(substr($value, $start, $offset - $start));
            self::skipOptionalWhitespace($value, $offset, $length);
            if ($offset === $length) {
                break;
            }
            if ($value[$offset] !== ',') {
                throw new InvalidEntityTag('If-None-Match entity tags must be comma separated.');
            }

            $offset++;
            self::skipOptionalWhitespace($value, $offset, $length);
            if ($offset === $length) {
                throw new InvalidEntityTag('If-None-Match cannot contain an empty list member.');
            }
        }

        return new self(false, $entityTags);
    }

    public function matches(HttpEntityTag $current): bool
    {
        if ($this->wildcard) {
            return true;
        }
        foreach ($this->entityTags as $candidate) {
            if ($current->weaklyEquals($candidate)) {
                return true;
            }
        }

        return false;
    }

    private static function rejectControlCharacters(string $value): void
    {
        $length = strlen($value);
        for ($offset = 0; $offset < $length; $offset++) {
            $code = ord($value[$offset]);
            if (($code < 0x20 && $code !== 0x09) || $code === 0x7F) {
                throw new InvalidEntityTag('If-None-Match cannot contain control characters.');
            }
        }
    }

    private static function skipOptionalWhitespace(string $value, int &$offset, int $length): void
    {
        while ($offset < $length && ($value[$offset] === ' ' || $value[$offset] === "\t")) {
            $offset++;
        }
    }

    private static function isOpaqueTagCharacter(int $code): bool
    {
        return $code === 0x21
            || ($code >= 0x23 && $code <= 0x7E)
            || $code >= 0x80;
    }
}
