<?php

namespace LBHurtado\XDocumentLaravel\Http;

use LBHurtado\XDocument\Browser\Host\BrowserContentDisposition;
use LBHurtado\XDocumentLaravel\Exceptions\UnsafeDocumentFilename;

final readonly class DocumentContentDisposition
{
    public function header(BrowserContentDisposition $disposition, string $filename): string
    {
        $this->assertSafe($filename);
        $fallback = preg_replace('/[^\x20-\x7E]/', '_', $filename);
        if (! is_string($fallback) || $fallback === '') {
            throw new UnsafeDocumentFilename('Document filename has no safe HTTP fallback.');
        }
        $fallback = addcslashes($fallback, '"\\');
        $header = $disposition->value.'; filename="'.$fallback.'"';
        if (preg_match('/[^\x20-\x7E]/', $filename) === 1) {
            $header .= "; filename*=UTF-8''".rawurlencode($filename);
        }

        return $header;
    }

    private function assertSafe(string $filename): void
    {
        if (
            $filename === ''
            || str_contains($filename, "\r")
            || str_contains($filename, "\n")
            || str_contains($filename, "\0")
            || str_contains($filename, '/')
            || str_contains($filename, '\\')
            || in_array($filename, ['.', '..'], true)
        ) {
            throw new UnsafeDocumentFilename('Document filename is unsafe for HTTP delivery.');
        }
    }
}
