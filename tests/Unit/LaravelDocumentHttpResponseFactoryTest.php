<?php

use Illuminate\Http\Request;
use LBHurtado\XDocument\Browser\Host\BrowserContentDisposition;
use LBHurtado\XDocument\Browser\Host\BrowserHostResponse;
use LBHurtado\XDocument\Browser\Host\BrowserRepresentation;
use LBHurtado\XDocument\Browser\Host\BrowserRepresentationDescriptor;
use LBHurtado\XDocument\Contract\DocumentOutput;
use LBHurtado\XDocumentLaravel\Data\DocumentHttpRequestContext;
use LBHurtado\XDocumentLaravel\Exceptions\UnsafeDocumentFilename;
use LBHurtado\XDocumentLaravel\Http\DocumentContentDisposition;
use LBHurtado\XDocumentLaravel\Http\LaravelDocumentHttpResponseFactory;
use Symfony\Component\HttpFoundation\Response;

function hostResponse(
    string $body = "<!doctype html>\n<html></html>\n",
    string $mediaType = 'text/html; charset=utf-8',
    string $filename = 'document.html',
    BrowserContentDisposition $disposition = BrowserContentDisposition::Inline,
): BrowserHostResponse {
    $descriptor = new BrowserRepresentationDescriptor(
        BrowserRepresentation::Html,
        'browser-html/1.0',
        $mediaType,
        BrowserContentDisposition::Inline,
        '.html',
        ['read_only'],
    );
    $output = DocumentOutput::inline($mediaType, $body, $filename);

    return new BrowserHostResponse(
        $descriptor,
        $output,
        $disposition,
        '"'.$output->checksum.'"',
        [],
    );
}

it('returns exact HTML bytes and deterministic representation headers', function () {
    $body = "<!doctype html>\n<html lang=\"en\">\n</html>\n";
    $source = hostResponse($body);
    $response = (new LaravelDocumentHttpResponseFactory)->make($source);

    expect($response)->toBeInstanceOf(Response::class)
        ->and($response->getStatusCode())->toBe(200)
        ->and($response->getContent())->toBe($body)
        ->and($response->headers->get('Content-Type'))->toBe('text/html; charset=utf-8')
        ->and($response->headers->get('Content-Disposition'))->toBe('inline; filename="document.html"')
        ->and($response->headers->get('ETag'))->toBe($source->etag)
        ->and($response->headers->get('Content-Length'))->toBe((string) strlen($body))
        ->and($response->headers->get('Cache-Control'))->toBe('no-cache, private')
        ->and($response->headers->get('X-Content-Type-Options'))->toBe('nosniff');
});

it('returns canonical JSON and arbitrary bytes without reserialization', function () {
    $json = "{\n    \"z\": 1,\n    \"a\": true\n}\n";
    $jsonResponse = hostResponse(
        $json,
        'application/vnd.3neti.x-document.browser-composition+json',
    );
    $binary = "prefix\0suffix\n";

    expect((new LaravelDocumentHttpResponseFactory)->make($jsonResponse)->getContent())->toBe($json)
        ->and((new LaravelDocumentHttpResponseFactory)->make(hostResponse($binary))->getContent())->toBe($binary);
});

it('returns styled semantic HTML without altering any byte', function () {
    $html = "<!doctype html>\n<style>.document { color: #123456; }</style>\n<article class=\"document\">Exact</article>\n";

    expect((new LaravelDocumentHttpResponseFactory)->make(hostResponse($html))->getContent())
        ->toBe($html);
});

it('honors attachment disposition and encodes safe Unicode filenames', function () {
    $response = (new LaravelDocumentHttpResponseFactory)->make(hostResponse(
        filename: 'Résumé final.html',
        disposition: BrowserContentDisposition::Attachment,
    ));

    expect($response->headers->get('Content-Disposition'))
        ->toBe("attachment; filename=\"R__sum__ final.html\"; filename*=UTF-8''R%C3%A9sum%C3%A9%20final.html");
});

it('quotes safe filename spaces and quotes deterministically', function () {
    $header = (new DocumentContentDisposition)->header(
        BrowserContentDisposition::Inline,
        'Board "final" copy.html',
    );

    expect($header)->toBe('inline; filename="Board \\"final\\" copy.html"');
});

it('rejects unsafe filename header values', function (string $filename) {
    (new LaravelDocumentHttpResponseFactory)->make(hostResponse(filename: $filename));
})->with([
    'carriage return' => ["document\runsafe.html"],
    'line feed' => ["document\nunsafe.html"],
])->throws(UnsafeDocumentFilename::class);

it('uses weak If-None-Match comparison including lists and wildcard for GET', function () {
    $source = hostResponse();
    $factory = new LaravelDocumentHttpResponseFactory;

    foreach ([
        $source->etag,
        'W/'.$source->etag,
        '"sha256:'.str_repeat('0', 64).'", '.$source->etag,
        '*',
    ] as $ifNoneMatch) {
        $response = $factory->make($source, DocumentHttpRequestContext::get($ifNoneMatch));
        expect($response->getStatusCode())->toBe(304)
            ->and($response->getContent())->toBe('')
            ->and($response->headers->get('ETag'))->toBe($source->etag);
    }
});

it('returns a bodyless 304 for a weakly matching HEAD request', function () {
    $source = hostResponse();
    $response = (new LaravelDocumentHttpResponseFactory)->make(
        $source,
        DocumentHttpRequestContext::head('W/'.$source->etag),
    );

    expect($response->getStatusCode())->toBe(304)
        ->and($response->getContent())->toBe('')
        ->and($response->headers->get('ETag'))->toBe($source->etag);
});

it('returns unchanged GET and HEAD responses for non-matching validators', function () {
    $body = "exact\nbytes\n";
    $source = hostResponse($body);
    $factory = new LaravelDocumentHttpResponseFactory;

    $get = $factory->make(
        $source,
        DocumentHttpRequestContext::get('W/"different"'),
    );
    $head = $factory->make(
        $source,
        DocumentHttpRequestContext::head('W/"different"'),
    );

    expect($get->getStatusCode())->toBe(200)
        ->and($get->getContent())->toBe($body)
        ->and($head->getStatusCode())->toBe(200)
        ->and($head->getContent())->toBe('')
        ->and($head->headers->get('Content-Length'))->toBe((string) strlen($body));
});

it('returns no HEAD body while retaining GET representation length', function () {
    $body = "exact\nbytes\n";
    $response = (new LaravelDocumentHttpResponseFactory)->make(
        hostResponse($body),
        DocumentHttpRequestContext::head(),
    );

    expect($response->getStatusCode())->toBe(200)
        ->and($response->getContent())->toBe('')
        ->and($response->headers->get('Content-Length'))->toBe((string) strlen($body))
        ->and($response->headers->get('Content-Type'))->toBe('text/html; charset=utf-8');
});

it('extracts only method and If-None-Match from a Laravel request', function () {
    $request = Request::create('/documents/example', 'HEAD', server: [
        'HTTP_IF_NONE_MATCH' => '"sha256:'.str_repeat('a', 64).'"',
        'HTTP_COOKIE' => 'secret=value',
        'HTTP_AUTHORIZATION' => 'Bearer secret',
    ]);
    $context = DocumentHttpRequestContext::fromLaravelRequest($request);

    expect($context->method)->toBe('HEAD')
        ->and($context->ifNoneMatch)->toBe('"sha256:'.str_repeat('a', 64).'"')
        ->and(get_object_vars($context))->toHaveCount(2);
});

it('rejects unsupported methods at the minimal request boundary', function () {
    new DocumentHttpRequestContext('POST', null);
})->throws(InvalidArgumentException::class);
