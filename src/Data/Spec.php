<?php

declare(strict_types=1);

namespace Hackerman\StorewireLite\Data;

/** One spec row; `code` is the filter code, when the spec is filterable. */
final readonly class Spec
{
    public function __construct(
        public string $label,
        public string $value,
        public ?string $code = null,
    ) {}

    /**
     * @return array{code?: string, label: string, value: string}
     */
    public function toArray(): array
    {
        return [...($this->code === null ? [] : ['code' => $this->code]), 'label' => $this->label, 'value' => $this->value];
    }
}
