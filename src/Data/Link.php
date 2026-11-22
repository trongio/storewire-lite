<?php

declare(strict_types=1);

namespace Hackerman\StorewireLite\Data;

/** A named page: a category, a breadcrumb step. */
final readonly class Link
{
    public function __construct(
        public string $name,
        public ?string $url = null,
    ) {}

    /**
     * @return array{name: string, url: ?string}
     */
    public function toArray(): array
    {
        return ['name' => $this->name, 'url' => $this->url];
    }
}
