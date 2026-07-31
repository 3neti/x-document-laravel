<?php

namespace LBHurtado\XDocumentLaravel\Tests\Fixtures\HostApplication;

use Illuminate\Http\Request;
use LBHurtado\XDocument\Browser\Host\BrowserContentDisposition;
use LBHurtado\XDocumentLaravel\Contracts\DocumentHttpResponseFactory;
use LBHurtado\XDocumentLaravel\Data\DocumentHttpRequestContext;
use Symfony\Component\HttpFoundation\Response;

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
            $request->query('representation', ExampleRepresentation::StyledCompositionHtml->value),
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
