<?php

declare(strict_types=1);

namespace Hackerman\StorewireLite\Data;

/** A product search as the search_products tool reads it. Prices in the store currency. */
final readonly class SearchQuery
{
    /**
     * @param  array<string, list<string>>  $filters  spec code => values; a numeric spec also takes "min~max"
     */
    public function __construct(
        public string $locale,
        public ?string $text = null,
        public ?string $category = null,
        public ?string $brand = null,
        public ?float $priceMin = null,
        public ?float $priceMax = null,
        public bool $inStock = false,
        public array $filters = [],
        public SortOrder $sort = SortOrder::Relevance,
        public int $page = 1,
        public int $perPage = 20,
    ) {}
}
