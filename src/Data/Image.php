<?php

declare(strict_types=1);

namespace Hackerman\StorewireLite\Data;

/** A product photo; URLs absolute. */
final readonly class Image
{
    public function __construct(
        public string $url,
        public string $thumbUrl,
        public string $alt = '',
    ) {}

    /**
     * @return array{url: string, thumb_url: string, alt: string}
     */
    public function toArray(): array
    {
        return ['url' => $this->url, 'thumb_url' => $this->thumbUrl, 'alt' => $this->alt];
    }
}
