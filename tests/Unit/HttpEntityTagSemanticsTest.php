<?php

use LBHurtado\XDocumentLaravel\Exceptions\InvalidEntityTag;
use LBHurtado\XDocumentLaravel\Http\HttpEntityTag;
use LBHurtado\XDocumentLaravel\Http\IfNoneMatch;

it('compares entity tags weakly by their exact opaque value', function (
    string $current,
    string $candidate,
    bool $matches,
) {
    $currentTag = str_starts_with($current, 'W/')
        ? HttpEntityTag::fromHeaderValue($current)
        : HttpEntityTag::fromCore($current);
    $candidateTag = HttpEntityTag::fromHeaderValue($candidate);

    expect($currentTag->weaklyEquals($candidateTag))->toBe($matches);
})->with([
    'strong and strong' => ['"abc"', '"abc"', true],
    'strong and weak' => ['"abc"', 'W/"abc"', true],
    'weak and strong' => ['W/"abc"', '"abc"', true],
    'weak and weak' => ['W/"abc"', 'W/"abc"', true],
    'different case' => ['"abc"', 'W/"ABC"', false],
    'different opaque value' => ['"abc"', 'W/"xyz"', false],
]);

it('preserves quoted commas while parsing ordered entity-tag lists', function (
    string $header,
    array $values,
) {
    $condition = IfNoneMatch::parse($header);

    expect(array_map(
        static fn (HttpEntityTag $tag): string => $tag->value,
        $condition->entityTags,
    ))->toBe($values);
})->with([
    'two strong tags' => ['"abc", "def"', ['"abc"', '"def"']],
    'weak then strong' => ['W/"abc", "def"', ['W/"abc"', '"def"']],
    'one tag containing comma' => ['"abc,def"', ['"abc,def"']],
    'weak comma tag then strong' => ['W/"abc,def", "xyz"', ['W/"abc,def"', '"xyz"']],
]);

it('matches wildcard only when it stands alone', function () {
    $current = HttpEntityTag::fromCore('"abc"');

    expect(IfNoneMatch::parse('*')->matches($current))->toBeTrue();
});

it('rejects malformed If-None-Match values', function (string $header) {
    IfNoneMatch::parse($header);
})->with([
    'empty' => [''],
    'whitespace only' => [" \t "],
    'bare token' => ['abc'],
    'weak bare token' => ['W/abc'],
    'weak wildcard' => ['W/*'],
    'unclosed quote' => ['"abc'],
    'missing opening quote' => ['abc"'],
    'trailing comma' => ['"abc",'],
    'leading comma' => [',"abc"'],
    'empty member' => ['"abc",, "def"'],
    'wildcard first in list' => ['*, "abc"'],
    'wildcard last in list' => ['"abc", *'],
    'carriage return' => ["\"abc\"\r"],
    'line feed' => ["\"abc\"\n"],
])->throws(InvalidEntityTag::class);

it('continues to require strongly quoted core entity tags', function (string $etag) {
    HttpEntityTag::fromCore($etag);
})->with([
    'weak' => ['W/"abc"'],
    'unquoted' => ['abc'],
    'multiple' => ['"abc", "def"'],
    'wildcard' => ['*'],
    'malformed' => ['"abc'],
])->throws(InvalidEntityTag::class);
