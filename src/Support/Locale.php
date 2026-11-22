<?php

declare(strict_types=1);

namespace Hackerman\StorewireLite\Support;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;

/**
 * The `locale` argument both tools take: one of `storewire-lite.locales`,
 * any case; blank or unknown reads as the default. Reading it also sets the
 * app locale for the rest of the call.
 */
final class Locale
{
    public static function from(Request $request): string
    {
        $given = $request->get('locale');
        $given = is_string($given) ? mb_strtolower(trim($given)) : '';
        $locale = in_array($given, self::supported(), true) ? $given : self::default();

        app()->setLocale($locale);

        return $locale;
    }

    public static function schema(JsonSchema $schema): Type
    {
        return $schema->string()
            ->enum(self::supported())
            ->description('Answer language: '.implode(', ', self::supported()).' (default '.self::default().'). Names, labels and URLs follow it.');
    }

    /** @return list<string> */
    public static function supported(): array
    {
        $supported = array_values(array_filter((array) config('storewire-lite.locales.supported'), is_string(...)));

        return $supported === [] ? [self::default()] : $supported;
    }

    public static function default(): string
    {
        $default = config('storewire-lite.locales.default');

        return is_string($default) && $default !== '' ? $default : 'en';
    }
}
