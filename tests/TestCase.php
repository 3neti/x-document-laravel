<?php

namespace LBHurtado\XDocumentLaravel\Tests;

use LBHurtado\XDocumentLaravel\XDocumentLaravelServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    /** @return list<class-string> */
    protected function getPackageProviders($app): array
    {
        return [XDocumentLaravelServiceProvider::class];
    }
}
