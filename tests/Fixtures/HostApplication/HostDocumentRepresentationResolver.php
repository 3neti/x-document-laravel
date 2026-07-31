<?php

namespace LBHurtado\XDocumentLaravel\Tests\Fixtures\HostApplication;

use LBHurtado\XDocument\Browser\Host\BrowserContentDisposition;
use LBHurtado\XDocument\Browser\Host\BrowserHostResponse;

interface HostDocumentRepresentationResolver
{
    public function resolve(
        string $documentId,
        ExampleRepresentation $representation,
        BrowserContentDisposition $disposition,
    ): BrowserHostResponse;
}
