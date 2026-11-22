<?php

declare(strict_types=1);

namespace Hackerman\StorewireLite\Data;

/** A catalogue menu node. */
final readonly class Category
{
    /**
     * @param  list<Category>  $children  empty past the requested depth
     */
    public function __construct(
        public string $name,
        public string $slug,
        public ?string $url,
        public int $productCount,
        public array $children = [],
    ) {}

    /**
     * @return array{name: string, slug: string, url: ?string, product_count: int, children: list<array<string, mixed>>}
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'slug' => $this->slug,
            'url' => $this->url,
            'product_count' => $this->productCount,
            'children' => array_map(static fn (Category $child): array => $child->toArray(), $this->children),
        ];
    }
}
