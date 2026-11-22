<?php

declare(strict_types=1);

namespace Hackerman\StorewireLite\Support;

use Hackerman\StorewireLite\Data\SearchQuery;

/** The two URLs Lite builds itself: absolute media and the store's search page. */
final class Urls
{
    /** A site-relative path made absolute under `app.url`; absolute URLs pass through. */
    public static function media(string $url): string
    {
        return str_starts_with($url, '/') && ! str_starts_with($url, '//') ? rtrim((string) config('app.url'), '/').$url : $url;
    }

    public static function searchPage(SearchQuery $query): string
    {
        $base = (string) config('storewire-lite.search_url');
        $words = (string) $query->text;

        if (str_contains($base, '{query}')) {
            return str_replace('{query}', rawurlencode($words), $base);
        }

        $params = array_filter([
            'q' => $query->text,
            'category' => $query->category,
            'brand' => $query->brand,
            'page' => $query->page > 1 ? $query->page : null,
        ], static fn (mixed $value): bool => $value !== null && $value !== '');

        return $base.($params === [] ? '' : (str_contains($base, '?') ? '&' : '?').http_build_query($params));
    }
}
