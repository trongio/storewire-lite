<?php

declare(strict_types=1);

namespace Hackerman\StorewireLite\Data;

/** A facet the search offers: `category`, or a spec code passed back in `filters`. */
final readonly class FilterGroup
{
    /**
     * @param  list<array{value: string, label: string, count: int}>  $options  count: products the value would leave
     */
    public function __construct(
        public string $code,
        public string $label,
        public array $options,
    ) {}
}
