<?php

namespace LBHurtado\XDocumentLaravel\Http;

use LBHurtado\XDocument\Browser\Host\BrowserHostResponse;
use LBHurtado\XDocumentLaravel\Contracts\DocumentHttpResponseFactory;
use LBHurtado\XDocumentLaravel\Data\DocumentHttpRequestContext;
use LBHurtado\XDocumentLaravel\Exceptions\InvalidDocumentHttpResponse;
use Symfony\Component\HttpFoundation\Response;

final readonly class LaravelDocumentHttpResponseFactory implements DocumentHttpResponseFactory
{
    public function __construct(
        private DocumentConditionalRequestEvaluator $conditionals = new DocumentConditionalRequestEvaluator,
        private DocumentContentDisposition $contentDisposition = new DocumentContentDisposition,
    ) {}

    public function make(
        BrowserHostResponse $response,
        ?DocumentHttpRequestContext $request = null,
    ): Response {
        $request ??= DocumentHttpRequestContext::get();
        $body = $response->output->inlineContent
            ?? throw new InvalidDocumentHttpResponse('Browser host response must contain inline bytes.');
        $this->assertIntegrity($response, $body);
        $etag = HttpEntityTag::fromCore($response->etag);
        $sharedHeaders = [
            'ETag' => $etag->value,
            'Cache-Control' => 'private, no-cache',
            'X-Content-Type-Options' => 'nosniff',
        ];
        if ($this->conditionals->isNotModified($request, $etag)) {
            return new Response('', Response::HTTP_NOT_MODIFIED, $sharedHeaders);
        }
        $headers = [
            ...$sharedHeaders,
            'Content-Type' => $response->output->mediaType,
            'Content-Disposition' => $this->contentDisposition->header(
                $response->disposition,
                $response->output->filename
                    ?? throw new InvalidDocumentHttpResponse('Browser host response filename is missing.'),
            ),
            'Content-Length' => (string) strlen($body),
        ];

        return new Response(
            $request->method === 'HEAD' ? '' : $body,
            Response::HTTP_OK,
            $headers,
        );
    }

    private function assertIntegrity(BrowserHostResponse $response, string $body): void
    {
        $mediaType = $response->output->mediaType;
        if (
            preg_match('/^[^\s\/;]+\/[^\s\/;]+(?:;\s*[A-Za-z0-9!#$&^_.+-]+=[A-Za-z0-9!#$&^_.+-]+)*$/D', $mediaType) !== 1
            || str_contains($mediaType, "\r")
            || str_contains($mediaType, "\n")
        ) {
            throw new InvalidDocumentHttpResponse('Browser host response media type is unsafe.');
        }
        if (
            $response->output->byteLength !== strlen($body)
            || $response->output->checksum !== 'sha256:'.hash('sha256', $body)
        ) {
            throw new InvalidDocumentHttpResponse('Browser host response body integrity is invalid.');
        }
    }
}
