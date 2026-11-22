<?php

declare(strict_types=1);

namespace Hackerman\StorewireLite\Data;

/** A titled group of the spec table. */
final readonly class SpecGroup
{
    /**
     * @param  list<Spec>  $specs
     */
    public function __construct(
        public ?string $name,
        public array $specs,
    ) {}

    /**
     * @return array{name: ?string, attributes: list<array<string, string>>}
     */
    public function toArray(): array
    {
        return ['name' => $this->name, 'attributes' => array_map(static fn (Spec $spec): array => $spec->toArray(), $this->specs)];
    }
}
