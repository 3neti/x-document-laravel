<?php

declare(strict_types=1);

use Composer\InstalledVersions;

require dirname(__DIR__).'/vendor/autoload.php';

$laravelMajor = (int) ($argv[1] ?? 0);
$expectedTestbenchMajor = $laravelMajor === 12 ? 10 : 11;
$expectedSymfonyMajor = $laravelMajor === 12 ? 7 : 8;

if (! in_array($laravelMajor, [12, 13], true)) {
    fwrite(STDERR, "Expected Laravel major 12 or 13.\n");
    exit(2);
}

/** @return non-empty-string */
$version = static function (string $package): string {
    $prettyVersion = InstalledVersions::getPrettyVersion($package);

    if ($prettyVersion === null || $prettyVersion === '') {
        throw new RuntimeException("Package {$package} is not installed.");
    }

    return $prettyVersion;
};

/** @param positive-int $expectedMajor */
$assertMajor = static function (string $package, int $expectedMajor) use ($version): string {
    $prettyVersion = $version($package);
    $normalizedVersion = InstalledVersions::getVersion($package) ?? $prettyVersion;

    if ((int) $normalizedVersion !== $expectedMajor) {
        throw new RuntimeException(
            "Expected {$package} major {$expectedMajor}; resolved {$prettyVersion}.",
        );
    }

    return $prettyVersion;
};

if (PHP_MAJOR_VERSION !== 8 || PHP_MINOR_VERSION !== 4) {
    throw new RuntimeException('The certified compatibility matrix requires PHP 8.4.');
}

$resolved = [
    'php' => PHP_VERSION,
    'laravel/framework' => $assertMajor('laravel/framework', $laravelMajor),
    'illuminate/components' => 'replaced by laravel/framework '.$version('laravel/framework'),
    'orchestra/testbench' => $assertMajor('orchestra/testbench', $expectedTestbenchMajor),
    'symfony/http-foundation' => $assertMajor('symfony/http-foundation', $expectedSymfonyMajor),
    '3neti/x-document' => $version('3neti/x-document'),
];

echo json_encode($resolved, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)."\n";
