<?php

declare(strict_types=1);

namespace Hackerman\StorewireLite\Data;

/** One product as its page shows it. */
final readonly class ProductDetail
{
    /**
     * @param  list<Image>  $images  main photo first
     * @param  string|null  $description  plain text
     * @param  list<SpecGroup>  $specs
     * @param  list<DocumentFile>  $documents
     * @param  list<Link>  $breadcrumb
     * @param  list<ProductCard>  $similar  alternatives
     * @param  list<RelatedProduct>  $goesWith  what it needs or works with
     */
    public function __construct(
        public ProductCard $card,
        public array $images = [],
        public ?string $description = null,
        public ?Warranty $warranty = null,
        public array $specs = [],
        public array $documents = [],
        public array $breadcrumb = [],
        public array $similar = [],
        public array $goesWith = [],
    ) {}
}
