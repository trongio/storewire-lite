<?php

declare(strict_types=1);

namespace Hackerman\StorewireLite\Support;

use Hackerman\StorewireLite\Data\ProductCard;
use Hackerman\StorewireLite\Data\StockState;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\ObjectType;
use Illuminate\JsonSchema\Types\Type;

/**
 * The product card as both tools answer it: the shape Storewire answers,
 * so a client written against Lite keeps working after an upgrade.
 */
final class Cards
{
    /** @return array<string, mixed> */
    public static function row(ProductCard $card): array
    {
        $row = $card->toArray();
        $image = $card->image;

        $row['image'] = $image === null ? null : [
            'url' => Urls::media($image->url),
            'thumb_url' => Urls::media($image->thumbUrl),
            'alt' => $image->alt !== '' ? $image->alt : $card->name,
        ];
        $row['price'] ??= [
            'amount' => null,
            'compare_at' => null,
            'currency' => self::currency(),
            'vat_included' => true,
            'on_request' => false,
        ];

        return $row;
    }

    public static function currency(): string
    {
        $currency = config('storewire-lite.currency');

        return is_string($currency) && $currency !== '' ? $currency : 'USD';
    }

    public static function schema(JsonSchema $schema): ObjectType
    {
        return $schema->object(self::properties($schema));
    }

    /** @return array<string, Type> */
    public static function properties(JsonSchema $schema): array
    {
        return [
            'public_id' => $schema->string()->description('Pass it to get_product.'),
            'name' => $schema->string(),
            'brand' => $schema->string()->nullable(),
            'model' => $schema->string()->nullable(),
            'url' => $schema->string()->nullable()->description('The product page on the store\'s website; cite it.'),
            'image' => self::imageSchema($schema)->nullable(),
            'price' => $schema->object([
                'amount' => $schema->number()->nullable()->description('The price a visitor pays; null when on request or not on sale.'),
                'compare_at' => $schema->number()->nullable()->description('The higher crossed-out price of a sale.'),
                'currency' => $schema->string(),
                'vat_included' => $schema->boolean(),
                'on_request' => $schema->boolean()->description('True: the price is given on request.'),
            ]),
            'availability' => $schema->object([
                'state' => $schema->string()->enum(StockState::values()),
                'label' => $schema->string()->description('The stock line the store\'s website shows.'),
                'branches' => $schema->array()->items($schema->object([
                    'name' => $schema->string(),
                    'state' => $schema->string(),
                ])),
            ]),
            'key_specs' => $schema->array()->items($schema->object([
                'label' => $schema->string(),
                'value' => $schema->string(),
            ])),
            'badges' => $schema->array()->items($schema->string())->description('For example new, sale or featured.'),
            'category' => $schema->object([
                'name' => $schema->string(),
                'url' => $schema->string()->nullable(),
            ])->nullable(),
        ];
    }

    public static function imageSchema(JsonSchema $schema): ObjectType
    {
        return $schema->object([
            'url' => $schema->string(),
            'thumb_url' => $schema->string(),
            'alt' => $schema->string(),
        ]);
    }
}
