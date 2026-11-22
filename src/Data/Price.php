<?php

declare(strict_types=1);

namespace Hackerman\StorewireLite\Data;

/** A price as a visitor sees it. A null amount: on request or not on sale. */
final readonly class Price
{
    public function __construct(
        public ?float $amount,
        public string $currency,
        public ?float $compareAt = null,
        public bool $vatIncluded = true,
        public bool $onRequest = false,
    ) {}

    public static function onRequest(string $currency, bool $vatIncluded = true): self
    {
        return new self(null, $currency, null, $vatIncluded, true);
    }

    /**
     * @return array{amount: ?float, compare_at: ?float, currency: string, vat_included: bool, on_request: bool}
     */
    public function toArray(): array
    {
        return [
            'amount' => $this->amount,
            'compare_at' => $this->compareAt,
            'currency' => $this->currency,
            'vat_included' => $this->vatIncluded,
            'on_request' => $this->onRequest,
        ];
    }
}
