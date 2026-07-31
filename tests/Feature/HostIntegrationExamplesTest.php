<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use LBHurtado\XDocument\Browser\Host\BrowserContentDisposition;
use LBHurtado\XDocumentLaravel\Contracts\DocumentHttpResponseFactory;
use LBHurtado\XDocumentLaravel\Data\DocumentHttpRequestContext;
use LBHurtado\XDocumentLaravel\Http\LaravelDocumentHttpResponseFactory;
use LBHurtado\XDocumentLaravel\Tests\Fixtures\HostApplication\ExampleRepresentation;
use LBHurtado\XDocumentLaravel\Tests\Fixtures\HostApplication\ExampleShowDocumentController;
use LBHurtado\XDocumentLaravel\Tests\Fixtures\HostApplication\FrozenDocumentRepresentationResolver;
use LBHurtado\XDocumentLaravel\Tests\Fixtures\HostApplication\HostDocumentAccessDenied;
use LBHurtado\XDocumentLaravel\Tests\Fixtures\HostApplication\HostDocumentAuthorizer;
use LBHurtado\XDocumentLaravel\Tests\Fixtures\HostApplication\HostDocumentNotFound;
use LBHurtado\XDocumentLaravel\Tests\Fixtures\HostApplication\HostDocumentRepresentationResolver;
use LBHurtado\XDocumentLaravel\Tests\Fixtures\HostApplication\RecordingDocumentAuthorizer;

beforeEach(function () {
    $authorization = new RecordingDocumentAuthorizer;
    $resolver = new FrozenDocumentRepresentationResolver($authorization);
    $this->app->instance(RecordingDocumentAuthorizer::class, $authorization);
    $this->app->instance(FrozenDocumentRepresentationResolver::class, $resolver);
    $this->app->instance(HostDocumentAuthorizer::class, $authorization);
    $this->app->instance(HostDocumentRepresentationResolver::class, $resolver);

    Route::match(['GET', 'HEAD'], '/example/documents/{documentId}', ExampleShowDocumentController::class);
    Route::get('/example/mapped/documents/{documentId}', function (
        Request $request,
        string $documentId,
        ExampleShowDocumentController $controller,
    ) {
        try {
            return $controller($request, $documentId);
        } catch (HostDocumentAccessDenied) {
            return response('Example host denied access.', 403);
        } catch (HostDocumentNotFound) {
            return response('Example host did not find the document.', 404);
        } catch (InvalidArgumentException) {
            return response('Example host rejected the request.', 400);
        }
    });
});

it('maps every example representation to consistent descriptor metadata', function (
    ExampleRepresentation $representation,
    string $format,
    string $mediaType,
    string $suffix,
    string $bodyCategory,
) {
    $authorization = $this->app->make(RecordingDocumentAuthorizer::class);
    $resolver = $this->app->make(FrozenDocumentRepresentationResolver::class);
    $authorization->assertMayView(null, 'document-1');

    $host = $resolver->resolve(
        'document-1',
        $representation,
        BrowserContentDisposition::Inline,
    );

    expect($host->descriptor->representation)->toBe($representation->coreRepresentation())
        ->and($host->descriptor->format)->toBe($format)
        ->and($host->descriptor->mediaType)->toBe($mediaType)
        ->and($host->descriptor->filenameSuffix)->toBe($suffix)
        ->and($host->descriptor->defaultDisposition)->toBe(BrowserContentDisposition::Inline)
        ->and($host->disposition)->toBe(BrowserContentDisposition::Inline)
        ->and($host->output->filename)->toEndWith($suffix)
        ->and($host->output->inlineContent)->toContain($bodyCategory);
})->with([
    'browser JSON' => [
        ExampleRepresentation::Json,
        'browser/1.0',
        'application/vnd.3neti.x-document.browser+json',
        '.browser.json',
        '"read_only": true',
    ],
    'semantic HTML' => [
        ExampleRepresentation::SemanticHtml,
        'browser-html/1.0',
        'text/html; charset=utf-8',
        '.html',
        '<article data-document="document-1">',
    ],
    'styled HTML' => [
        ExampleRepresentation::StyledHtml,
        'browser-html-styled/1.0',
        'text/html; charset=utf-8',
        '.styled.html',
        '<style>.document',
    ],
    'styled composition HTML' => [
        ExampleRepresentation::StyledCompositionHtml,
        'browser-composition-html-styled/1.0',
        'text/html; charset=utf-8',
        '.composition.styled.html',
        'data-interaction="approve"',
    ],
]);

it('supports framework-light direct factory usage with exact bytes', function () {
    $authorization = new RecordingDocumentAuthorizer;
    $authorization->assertMayView(null, 'document-1');
    $host = (new FrozenDocumentRepresentationResolver($authorization))->resolve(
        'document-1',
        ExampleRepresentation::Json,
        BrowserContentDisposition::Inline,
    );

    $response = (new LaravelDocumentHttpResponseFactory)->make(
        $host,
        DocumentHttpRequestContext::get(),
    );

    expect($response->getContent())->toBe($host->output->inlineContent)
        ->and($response->headers->get('Content-Type'))->toBe($host->output->mediaType)
        ->and($response->headers->get('ETag'))->toBe($host->etag);
});

it('delivers each allowlisted representation without changing its bytes', function (
    ExampleRepresentation $representation,
) {
    $resolver = $this->app->make(FrozenDocumentRepresentationResolver::class);
    $response = $this->get('/example/documents/document-1?representation='.$representation->value);
    expect($resolver->invocations)->toBe(1);
    $host = $resolver->lastResolvedResponse();

    $response->assertSuccessful()
        ->assertHeader('Content-Type', $host->output->mediaType)
        ->assertHeader('ETag', $host->etag)
        ->assertHeader('Content-Length', (string) $host->output->byteLength)
        ->assertHeader('Cache-Control', 'no-cache, private')
        ->assertHeader('X-Content-Type-Options', 'nosniff');
    expect($response->getContent())->toBe($host->output->inlineContent)
        ->and($response->headers->get('Content-Disposition'))->toStartWith('inline;');
})->with(ExampleRepresentation::cases());

it('resolves once for HEAD and retains the GET representation length', function () {
    $resolver = $this->app->make(HostDocumentRepresentationResolver::class);
    $response = $this->call(
        'HEAD',
        '/example/documents/document-1',
        ['representation' => ExampleRepresentation::StyledCompositionHtml->value],
    );

    $response->assertSuccessful()->assertContent('');
    expect($resolver->invocations)->toBe(1)
        ->and($response->headers->get('Content-Length'))->toBeGreaterThan(0);
});

it('supports strong and weak conditional GET followed by bodyless 304', function (bool $weak) {
    $first = $this->get('/example/documents/document-1');
    $etag = $first->headers->get('ETag');
    $validator = $weak ? 'W/'.$etag : $etag;
    $second = $this->withHeader('If-None-Match', $validator)
        ->get('/example/documents/document-1');

    $first->assertSuccessful();
    $second->assertStatus(304)->assertContent('')->assertHeader('ETag', $etag);
})->with([
    'strong validator' => [false],
    'weak validator' => [true],
]);

it('supports conditional HEAD without a second resolution in one request', function () {
    $first = $this->get('/example/documents/document-1');
    $resolver = $this->app->make(HostDocumentRepresentationResolver::class);
    $before = $resolver->invocations;
    $response = $this->call(
        'HEAD',
        '/example/documents/document-1',
        server: ['HTTP_IF_NONE_MATCH' => 'W/'.$first->headers->get('ETag')],
    );

    $response->assertStatus(304)->assertContent('');
    expect($resolver->invocations - $before)->toBe(1);
});

it('expresses host-selected attachment disposition with a safe Unicode filename', function () {
    $response = $this->get('/example/documents/document-1?download=1');

    $response->assertSuccessful();
    expect($response->headers->get('Content-Disposition'))
        ->toBe("attachment; filename=\"r__sum__-document.composition.styled.html\"; filename*=UTF-8''r%C3%A9sum%C3%A9-document.composition.styled.html");
});

it('authorizes before resolving and keeps authorization mapping host owned', function () {
    $resolver = $this->app->make(HostDocumentRepresentationResolver::class);

    $this->get('/example/mapped/documents/forbidden')->assertForbidden()
        ->assertContent('Example host denied access.');
    expect($resolver->invocations)->toBe(0);
});

it('rejects invalid representations before resolution with host-owned mapping', function () {
    $resolver = $this->app->make(HostDocumentRepresentationResolver::class);

    $this->get('/example/mapped/documents/document-1?representation=arbitrary')
        ->assertStatus(400)
        ->assertContent('Example host rejected the request.');
    expect($resolver->invocations)->toBe(0);
});

it('maps unknown documents in the example host after one resolution attempt', function () {
    $resolver = $this->app->make(HostDocumentRepresentationResolver::class);

    $this->get('/example/mapped/documents/unknown')->assertNotFound()
        ->assertContent('Example host did not find the document.');
    expect($resolver->invocations)->toBe(1);
});

it('keeps conditional integration exception mapping in the example host', function () {
    $response = $this->withHeader('If-None-Match', 'malformed')
        ->get('/example/mapped/documents/document-1');

    $response->assertStatus(400)
        ->assertContent('Example host rejected the request.');
});

it('injects the public response factory contract into the example controller', function () {
    $controller = $this->app->make(ExampleShowDocumentController::class);

    expect($controller)->toBeInstanceOf(ExampleShowDocumentController::class)
        ->and($this->app->make(DocumentHttpResponseFactory::class))
        ->toBeInstanceOf(LaravelDocumentHttpResponseFactory::class);
});
