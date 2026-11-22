<?php

declare(strict_types=1);

namespace Hackerman\StorewireLite\Contracts;

use Hackerman\StorewireLite\Data\ProductDetail;
use Hackerman\StorewireLite\Data\SearchQuery;
use Hackerman\StorewireLite\Data\SearchResult;

/**
 * The store's catalogue as a guest sees it: the part of Storewire's
 * `CatalogRepository` that search_products and get_product read. Same
 * method signatures and the same data objects, so an implementation moves
 * to the full Storewire contract by switching the namespace and adding the
 * remaining methods (cards, compare, compatibility, categories).
 *
 * Every method answers only products a visitor may see; names, labels and
 * URLs in `$locale`.
 */
interface CatalogRepository
{
    /**
     * The id of the product a shopper's reference names: an id from an
     * earlier answer, a product URL or slug, a model number or SKU. Null
     * when nothing visible matches.
     */
    public function resolve(string $reference, string $locale): ?string;

    /** One product as its page shows it; null when it is not visible. */
    public function product(string $id, string $locale): ?ProductDetail;

    public function search(SearchQuery $query): SearchResult;
}
