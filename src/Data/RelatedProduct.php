<?php

declare(strict_types=1);

namespace Hackerman\StorewireLite\Data;

/** A product that goes with another, and how (in the answer locale: requires, accessory, works with). */
final readonly class RelatedProduct
{
    public function __construct(
        public ProductCard $card,
        public string $relation,
    ) {}
}
