<?php

declare(strict_types=1);

namespace Hackerman\StorewireLite\Data;

/** The stock states a product card can be in. */
enum StockState: string
{
    case InStock = 'in_stock';
    case LowStock = 'low_stock';
    case OnOrder = 'on_order';
    case OnRequest = 'on_request';
    case OutOfStock = 'out_of_stock';
    case NotSold = 'not_sold';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
