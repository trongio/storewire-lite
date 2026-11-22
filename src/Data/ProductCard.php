<?php

declare(strict_types=1);

namespace Hackerman\StorewireLite\Data;

/**
 * The product shape of every answer: a search hit, the product get_product
 * describes, its rails. `id` is the stable public id tools pass around
 * (never a database key). Lite answers with the card's own `price`; a card
 * without one says so (amount null).
 */
final readonly class ProductCard
{
    /**
     * @param  list<Spec>  $keySpecs  a handful, most telling first
     * @param  list<string>  $badges  e.g. new, sale, featured
     */
    public function __construct(
        public string $id,
        public string $name,
        public ?string $url,
        public Availability $availability,
        public ?Price $price = null,
        public ?string $brand = null,
        public ?string $model = null,
        public ?Image $image = null,
        public array $keySpecs = [],
        public array $badges = [],
        public ?Link $category = null,
    ) {}

    public function withPrice(Price $price): self
    {
        return new self($this->id, $this->name, $this->url, $this->availability, $price, $this->brand, $this->model, $this->image, $this->keySpecs, $this->badges, $this->category);
    }

    public function ref(): ProductRef
    {
        return new ProductRef($this->id, $this->name, $this->url);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'public_id' => $this->id,
            'name' => $this->name,
            'brand' => $this->brand,
            'model' => $this->model,
            'url' => $this->url,
            'image' => $this->image?->toArray(),
            'price' => $this->price?->toArray(),
            'availability' => $this->availability->toArray(),
            'key_specs' => array_map(static fn (Spec $spec): array => ['label' => $spec->label, 'value' => $spec->value], $this->keySpecs),
            'badges' => $this->badges,
            'category' => $this->category?->toArray(),
        ];
    }
}
