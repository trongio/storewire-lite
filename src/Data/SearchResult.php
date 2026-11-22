<?php

declare(strict_types=1);

namespace Hackerman\StorewireLite\Data;

/** One page of search hits plus the facets to narrow them. */
final readonly class SearchResult
{
    /**
     * @param  list<ProductCard>  $products
     * @param  list<FilterGroup>  $suggestedFilters
     * @param  list<array{code: string, label: string}>  $appliedFilters
     * @param  list<Category>  $didYouMeanCategories  only when nothing matched
     * @param  list<ProductCard>  $didYouMeanProducts  only when nothing matched
     */
    public function __construct(
        public array $products,
        public int $total,
        public int $page,
        public int $perPage,
        public array $suggestedFilters = [],
        public array $appliedFilters = [],
        public array $didYouMeanCategories = [],
        public array $didYouMeanProducts = [],
    ) {}
}
