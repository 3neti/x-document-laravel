<?php

use LBHurtado\XDocumentLaravel\Contracts\DocumentHttpResponseFactory;
use LBHurtado\XDocumentLaravel\Http\LaravelDocumentHttpResponseFactory;

arch('package source excludes business persistence execution and frontend concerns')
    ->expect('LBHurtado\XDocumentLaravel')
    ->not->toUse([
        'App',
        'GNE',
        'Eloquent',
        'Illuminate\Database',
        'Illuminate\Auth',
        'Illuminate\Queue',
        'Illuminate\Routing',
        'Illuminate\View',
        'Inertia',
        'Vue',
        'React',
        'Livewire',
        'Blade',
        'Settlement',
        'Workflow',
    ]);

arch('the concrete adapter implements the single public response factory')
    ->expect(LaravelDocumentHttpResponseFactory::class)
    ->toImplement(DocumentHttpResponseFactory::class);

it('preserves core bytes without JSON HTML filesystem network or execution machinery', function () {
    $root = dirname(__DIR__, 2);
    $source = '';
    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root.'/src')) as $file) {
        if ($file instanceof SplFileInfo && $file->isFile()) {
            $source .= file_get_contents($file->getPathname())."\n";
        }
    }

    expect($source)->not->toContain(
        'json_encode',
        'json_decode',
        'response()->json',
        'response()->view',
        'StreamedResponse',
        'file_get_contents',
        'file_put_contents',
        'curl_',
        '<script',
        'dispatch(',
        'catch (Throwable',
    );
});

it('declares package discovery and no routes views migrations or commands', function () {
    $root = dirname(__DIR__, 2);
    $composer = json_decode(file_get_contents($root.'/composer.json') ?: '', true, flags: JSON_THROW_ON_ERROR);
    $source = file_get_contents($root.'/src/XDocumentLaravelServiceProvider.php');

    expect($composer['require'])->toHaveKey('3neti/x-document')
        ->and($composer['extra']['laravel']['providers'])
        ->toBe(['LBHurtado\\XDocumentLaravel\\XDocumentLaravelServiceProvider'])
        ->and($source)->not->toContain(
            'loadRoutesFrom',
            'loadViewsFrom',
            'loadMigrationsFrom',
            'commands(',
            'publishes(',
        );
});

it('centralizes conditional entity-tag parsing outside the response factory', function () {
    $root = dirname(__DIR__, 2);
    $factory = file_get_contents($root.'/src/Http/LaravelDocumentHttpResponseFactory.php');
    $evaluator = file_get_contents($root.'/src/Http/DocumentConditionalRequestEvaluator.php');
    $parser = file_get_contents($root.'/src/Http/IfNoneMatch.php');

    expect($factory)->toContain('DocumentConditionalRequestEvaluator')
        ->not->toContain('If-None-Match', 'preg_split', 'explode(')
        ->and($evaluator)->toContain('IfNoneMatch::parse')
        ->and($parser)->not->toContain('preg_split', 'explode(')
        ->and($factory.$evaluator.$parser)->not->toContain(
            'If-Match',
            'If-Modified-Since',
            'If-Unmodified-Since',
            'If-Range',
            'Range',
        );
});

it('keeps host application examples outside production runtime', function () {
    $root = dirname(__DIR__, 2);
    $provider = file_get_contents($root.'/src/XDocumentLaravelServiceProvider.php');
    $composer = json_decode(file_get_contents($root.'/composer.json') ?: '', true, flags: JSON_THROW_ON_ERROR);

    expect(is_dir($root.'/tests/Fixtures/HostApplication'))->toBeTrue()
        ->and(is_dir($root.'/src/Controllers'))->toBeFalse()
        ->and(is_dir($root.'/src/Authorization'))->toBeFalse()
        ->and(is_dir($root.'/routes'))->toBeFalse()
        ->and($composer['autoload']['psr-4'])->not->toHaveKey(
            'LBHurtado\\XDocumentLaravel\\Tests\\',
        )
        ->and($composer['autoload-dev']['psr-4'])->toHaveKey(
            'LBHurtado\\XDocumentLaravel\\Tests\\',
            'tests/',
        )
        ->and($provider)->not->toContain(
            'HostApplication',
            'loadRoutesFrom',
            'Route::',
            'policy(',
            'ExceptionHandler',
        );
});
