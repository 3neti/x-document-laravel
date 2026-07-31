<?php

declare(strict_types=1);

use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\PackageManifest;
use LBHurtado\XDocumentLaravel\Contracts\DocumentHttpResponseFactory;
use LBHurtado\XDocumentLaravel\Http\LaravelDocumentHttpResponseFactory;
use LBHurtado\XDocumentLaravel\XDocumentLaravelServiceProvider;

$hostRoot = $argv[1] ?? null;

if (! is_string($hostRoot) || ! is_dir($hostRoot.'/vendor')) {
    fwrite(STDERR, "Expected a composed host directory.\n");
    exit(2);
}

require $hostRoot.'/vendor/autoload.php';

$cachePath = $hostRoot.'/bootstrap/cache/packages.php';
if (! is_dir(dirname($cachePath)) && ! mkdir(dirname($cachePath), 0777, true)) {
    throw new RuntimeException('Unable to create the host package-discovery cache directory.');
}

$manifest = new PackageManifest(new Filesystem, $hostRoot, $cachePath);
$manifest->build();
$providers = $manifest->providers();

if (! in_array(XDocumentLaravelServiceProvider::class, $providers, true)) {
    throw new RuntimeException('Composer package discovery did not find the x-document Laravel provider.');
}

$application = new Application($hostRoot);
foreach ($providers as $provider) {
    if ($provider === XDocumentLaravelServiceProvider::class) {
        $application->register($provider);
    }
}

$factory = $application->make(DocumentHttpResponseFactory::class);

if (! $factory instanceof LaravelDocumentHttpResponseFactory) {
    throw new RuntimeException('The discovered provider did not register the response factory binding.');
}

echo "Package discovery: provider discovered and response factory resolved.\n";
