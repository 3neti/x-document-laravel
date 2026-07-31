<?php

use Illuminate\Http\Request;
use LBHurtado\XDocument\Browser\Host\BrowserContentDisposition;
use LBHurtado\XDocument\Browser\Host\BrowserHostResponse;
use LBHurtado\XDocument\Browser\Host\BrowserRepresentation;
use LBHurtado\XDocument\Browser\Host\BrowserRepresentationDescriptor;
use LBHurtado\XDocument\Contract\DocumentOutput;
use LBHurtado\XDocumentLaravel\Contracts\DocumentHttpResponseFactory;
use LBHurtado\XDocumentLaravel\Data\DocumentHttpRequestContext;
use LBHurtado\XDocumentLaravel\Http\LaravelDocumentHttpResponseFactory;

it('binds the public response factory through the package service provider', function () {
    $factory = $this->app->make(DocumentHttpResponseFactory::class);

    expect($factory)->toBeInstanceOf(LaravelDocumentHttpResponseFactory::class)
        ->and($this->app->make(DocumentHttpResponseFactory::class))->toBe($factory);
});

it('supports controller-style request extraction and exact response delivery', function () {
    $body = "{\n    \"canonical\": true\n}\n";
    $descriptor = new BrowserRepresentationDescriptor(
        BrowserRepresentation::Composition,
        'browser-composition/1.0',
        'application/vnd.3neti.x-document.browser-composition+json',
        BrowserContentDisposition::Inline,
        '.composition.json',
        ['read_only'],
    );
    $output = DocumentOutput::inline(
        $descriptor->mediaType,
        $body,
        'document.composition.json',
    );
    $host = new BrowserHostResponse(
        $descriptor,
        $output,
        BrowserContentDisposition::Inline,
        '"'.$output->checksum.'"',
        [],
    );
    $request = Request::create('/documents/example', 'GET');
    $response = $this->app->make(DocumentHttpResponseFactory::class)->make(
        $host,
        DocumentHttpRequestContext::fromLaravelRequest($request),
    );

    expect($response->getContent())->toBe($body)
        ->and($response->headers->get('Content-Type'))->toBe($descriptor->mediaType)
        ->and($response->headers->get('ETag'))->toBe($host->etag)
        ->and($response->headers->get('Content-Length'))->toBe((string) strlen($body));
});

it('requires no routes views migrations database or middleware', function () {
    $root = dirname(__DIR__, 2);

    expect(is_dir($root.'/routes'))->toBeFalse()
        ->and(is_dir($root.'/resources/views'))->toBeFalse()
        ->and(is_dir($root.'/database'))->toBeFalse()
        ->and(is_dir($root.'/src/Http/Middleware'))->toBeFalse();
});
