<?php

declare(strict_types=1);

namespace Hackerman\StorewireLite\Data;

/** A downloadable file: datasheet, manual, firmware, certificate. */
final readonly class DocumentFile
{
    /**
     * @param  string|null  $size  human-readable, e.g. "2.4 MB"
     * @param  bool  $external  hosted by the manufacturer, not the store
     */
    public function __construct(
        public string $id,
        public string $type,
        public ?string $title,
        public string $downloadUrl,
        public ?string $openUrl = null,
        public ?string $language = null,
        public ?string $version = null,
        public ?string $size = null,
        public bool $external = false,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'public_id' => $this->id,
            'type' => $this->type,
            'title' => $this->title,
            'language' => $this->language,
            'version' => $this->version,
            'size' => $this->size,
            'download_url' => $this->downloadUrl,
            'open_url' => $this->openUrl,
            'external' => $this->external,
        ];
    }
}
