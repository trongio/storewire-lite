<?php

declare(strict_types=1);

namespace Hackerman\StorewireLite\Data;

/** The least a product answer names: id, name and page. */
final readonly class ProductRef
{
    public function __construct(
        public string $id,
        public string $name,
        public ?string $url = null,
    ) {}

    /**
     * @return array{public_id: string, name: string, url: ?string}
     */
    public function toArray(): array
    {
        return ['public_id' => $this->id, 'name' => $this->name, 'url' => $this->url];
    }
}
