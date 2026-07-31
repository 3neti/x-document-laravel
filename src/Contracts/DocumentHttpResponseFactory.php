<?php

namespace LBHurtado\XDocumentLaravel\Contracts;

use LBHurtado\XDocument\Browser\Host\BrowserHostResponse;
use LBHurtado\XDocumentLaravel\Data\DocumentHttpRequestContext;
use Symfony\Component\HttpFoundation\Response;

interface DocumentHttpResponseFactory
{
    public function make(
        BrowserHostResponse $response,
        ?DocumentHttpRequestContext $request = null,
    ): Response;
}
