<?php

declare(strict_types=1);

namespace Hackerman\StorewireLite\Data;

/** The stock line of a product, worded as the storefront words it. */
final readonly class Availability
{
    /**
     * @param  string  $label  the stock line in the answer locale
     * @param  list<array{name: string, state: string}>  $locations  stores or warehouses holding it
     */
    public function __construct(
        public StockState $state,
        public string $label,
        public array $locations = [],
    ) {}

    /**
     * @return array{state: string, label: string, branches: list<array{name: string, state: string}>}
     */
    public function toArray(): array
    {
        return ['state' => $this->state->value, 'label' => $this->label, 'branches' => $this->locations];
    }
}
