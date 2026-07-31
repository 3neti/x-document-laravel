<?php

namespace LBHurtado\XDocumentLaravel\Tests\Fixtures\HostApplication;

use LBHurtado\XDocument\Browser\Host\BrowserRepresentation;

enum ExampleRepresentation: string
{
    case Json = 'browser-json';
    case SemanticHtml = 'browser-html';
    case StyledHtml = 'browser-html-styled';
    case StyledCompositionHtml = 'browser-composition-html-styled';

    public static function fromRequest(mixed $value): self
    {
        if (! is_string($value) || self::tryFrom($value) === null) {
            throw new \InvalidArgumentException('The example representation is not allowed.');
        }

        return self::from($value);
    }

    public function coreRepresentation(): BrowserRepresentation
    {
        return BrowserRepresentation::from($this->value);
    }
}
