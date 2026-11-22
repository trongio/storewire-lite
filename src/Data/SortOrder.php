<?php

declare(strict_types=1);

namespace Hackerman\StorewireLite\Data;

enum SortOrder: string
{
    case Relevance = 'relevance';
    case PriceAsc = 'price_asc';
    case PriceDesc = 'price_desc';
    case Newest = 'newest';
}
