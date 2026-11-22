<?php

declare(strict_types=1);

namespace Hackerman\StorewireLite\Support;

use Illuminate\Support\Str;

/**
 * Free text on its way in and out of the server.
 *
 * Out: descriptions, FAQ answers, warranty lines, comparison values. It
 * lands in other people's assistants, so it leaves as plain text only: tags
 * stripped (a block tag becomes a space, so words never fuse), entities
 * decoded, whitespace collapsed, capped at a character budget with an
 * ellipsis. No markup, no links hidden in an anchor.
 *
 * In: laravel/mcp does not enforce an input schema's bounds, so a free-text
 * argument (a query, a slug, a brand, a city) is cut to
 * {@see self::INPUT_CHARS} before any lookup reads it, and what a caller sent
 * is echoed back (an error, a rejected reference) at most
 * {@see self::ECHO_CHARS} long.
 */
final class PublicText
{
    /** The longest free-text argument a tool reads. */
    public const int INPUT_CHARS = 200;

    /** The longest piece of a caller's own input an answer repeats. */
    public const int ECHO_CHARS = 80;

    public static function plain(?string $html, ?int $maxChars = null): ?string
    {
        if ($html === null) {
            return null;
        }

        $spaced = (string) preg_replace('/<(?:br|\/?(?:p|div|li|ul|ol|h[1-6]|tr|td|th|table|section|article))\b[^>]*>/i', ' ', $html);
        $text = Str::squish(html_entity_decode(strip_tags($spaced), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        $max = $maxChars ?? self::budget();

        return $text === '' ? null : Str::limit($text, $max, '…', preserveWords: true);
    }

    /** The free-text budget, `storewire-lite.limits.text_chars`. */
    public static function budget(): int
    {
        return max(1, (int) config('storewire-lite.limits.text_chars', 1500));
    }

    /** A free-text argument as {@see Input::string()} reads it, cut to {@see self::INPUT_CHARS}. */
    public static function argument(mixed $value): ?string
    {
        $text = Input::string($value);

        return $text === null ? null : mb_substr($text, 0, self::INPUT_CHARS);
    }

    /** A caller's input as an answer may repeat it. */
    public static function echo(string $given): string
    {
        return Str::limit($given, self::ECHO_CHARS);
    }
}
