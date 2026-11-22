<?php

declare(strict_types=1);

use Symfony\Component\Finder\Finder;

/*
 * What ships stays neutral: no source-store name, no icon-font kit, no
 * assistant brand in what a shopper or a directory reads, and no em dash.
 * CI runs the same search over the whole package.
 */

/**
 * @return list<string>
 */
function liteShippedFiles(): array
{
    $root = dirname(__DIR__, 2);
    $files = Finder::create()->files()->in([$root.'/src', $root.'/config'])->append([$root.'/README.md', $root.'/composer.json', $root.'/LICENSE']);

    return array_map(static fn (SplFileInfo $file): string => $file->getPathname(), iterator_to_array($files, false));
}

it('ships no source-store name, icon-font kit or assistant brand', function (): void {
    $banned = '/'.implode('|', ['all'.'digital', 'font'.'awesome', 'fa'.'-light', 'chat'.'gpt', '\bcl'.'aude\b', 'anthr'.'opic']).'/i';

    foreach (liteShippedFiles() as $file) {
        expect((string) file_get_contents($file))->not->toMatch($banned, $file);
    }
});

it('ships no em dash', function (): void {
    foreach (liteShippedFiles() as $file) {
        expect((string) file_get_contents($file))->not->toContain("\u{2014}", $file);
    }
});

it('depends on no paid package', function (): void {
    $composer = json_decode((string) file_get_contents(dirname(__DIR__, 2).'/composer.json'), true);

    expect(array_keys($composer['require']))->toBe(['php', 'illuminate/support', 'laravel/mcp']);
});
