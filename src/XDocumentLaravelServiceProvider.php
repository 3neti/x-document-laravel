<?php

namespace LBHurtado\XDocumentLaravel;

use Illuminate\Support\ServiceProvider;
use LBHurtado\XDocumentLaravel\Contracts\DocumentHttpResponseFactory;
use LBHurtado\XDocumentLaravel\Http\LaravelDocumentHttpResponseFactory;

final class XDocumentLaravelServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(
            DocumentHttpResponseFactory::class,
            LaravelDocumentHttpResponseFactory::class,
        );
    }
}
