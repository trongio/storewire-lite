<?php

declare(strict_types=1);

namespace Hackerman\StorewireLite\Tools;

use Hackerman\StorewireLite\Support\Locale;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

/**
 * A read-only catalogue lookup: annotated so for every client, its input
 * schema ending with the shared `locale` argument.
 */
abstract class LiteTool extends Tool
{
    /**
     * @return array<string, bool>
     */
    public function annotations(): array
    {
        return [
            'readOnlyHint' => true,
            'destructiveHint' => false,
            'openWorldHint' => false,
            'idempotentHint' => true,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    final public function schema(JsonSchema $schema): array
    {
        return [...$this->arguments($schema), 'locale' => Locale::schema($schema)];
    }

    /**
     * @return array<string, mixed>
     */
    abstract protected function arguments(JsonSchema $schema): array;

    protected static function store(): string
    {
        $name = config('storewire-lite.server.name');

        return is_string($name) && $name !== '' ? $name : 'the store';
    }

    protected static function limit(string $key, int $default): int
    {
        return max(1, (int) config('storewire-lite.limits.'.$key, $default));
    }

    protected static function notFound(): Response
    {
        return Response::error('No product matches that reference, or it is not on sale. Search with search_products and pass a public_id from its answer.');
    }
}
