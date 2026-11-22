<?php

declare(strict_types=1);

namespace Hackerman\StorewireLite\Data;

final readonly class Warranty
{
    public function __construct(
        public int $months,
        public ?string $text = null,
    ) {}

    /**
     * @return array{months: int, text: ?string}
     */
    public function toArray(): array
    {
        return ['months' => $this->months, 'text' => $this->text];
    }
}
