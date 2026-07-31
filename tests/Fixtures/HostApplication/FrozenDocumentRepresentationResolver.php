<?php

namespace LBHurtado\XDocumentLaravel\Tests\Fixtures\HostApplication;

use LBHurtado\XDocument\Browser\Host\BrowserContentDisposition;
use LBHurtado\XDocument\Browser\Host\BrowserHostResponse;
use LBHurtado\XDocument\Browser\Host\BrowserRepresentationDescriptor;
use LBHurtado\XDocument\Contract\DocumentOutput;

final class FrozenDocumentRepresentationResolver implements HostDocumentRepresentationResolver
{
    public int $invocations = 0;

    public function __construct(
        private readonly RecordingDocumentAuthorizer $authorization,
    ) {}

    public function resolve(
        string $documentId,
        ExampleRepresentation $representation,
        BrowserContentDisposition $disposition,
    ): BrowserHostResponse {
        if (! $this->authorization->hasAuthorized($documentId)) {
            throw new \LogicException('The example host must authorize before resolving.');
        }

        $this->invocations++;
        if ($documentId !== 'document-1') {
            throw new HostDocumentNotFound('The example host could not find the document.');
        }

        [$format, $mediaType, $suffix, $body] = $this->representationFacts($representation);
        $descriptor = new BrowserRepresentationDescriptor(
            $representation->coreRepresentation(),
            $format,
            $mediaType,
            BrowserContentDisposition::Inline,
            $suffix,
            ['read_only'],
        );
        $output = DocumentOutput::inline(
            $mediaType,
            $body,
            'résumé-document'.$suffix,
        );

        return new BrowserHostResponse(
            $descriptor,
            $output,
            $disposition,
            '"'.$output->checksum.'"',
            ['example' => true],
        );
    }

    /** @return array{string, string, string, string} */
    private function representationFacts(ExampleRepresentation $representation): array
    {
        return match ($representation) {
            ExampleRepresentation::Json => [
                'browser/1.0',
                'application/vnd.3neti.x-document.browser+json',
                '.browser.json',
                "{\n    \"document\": \"document-1\",\n    \"read_only\": true\n}\n",
            ],
            ExampleRepresentation::SemanticHtml => [
                'browser-html/1.0',
                'text/html; charset=utf-8',
                '.html',
                "<!doctype html>\n<article data-document=\"document-1\">Semantic</article>\n",
            ],
            ExampleRepresentation::StyledHtml => [
                'browser-html/1.0',
                'text/html; charset=utf-8',
                '.styled.html',
                "<!doctype html>\n<style>.document{color:#123456}</style>\n<article class=\"document\">Styled</article>\n",
            ],
            ExampleRepresentation::StyledCompositionHtml => [
                'browser-composition-html/1.0',
                'text/html; charset=utf-8',
                '.composition.styled.html',
                "<!doctype html>\n<style>.composition{display:block}</style>\n<main class=\"composition\" data-interaction=\"approve\">Composed</main>\n",
            ],
        };
    }
}
