<?php

declare(strict_types=1);

namespace Hackerman\StorewireLite\Server;

use Hackerman\StorewireLite\Tools\GetProductTool;
use Hackerman\StorewireLite\Tools\SearchProductsTool;
use Laravel\Mcp\Server;

/** The read-only storefront server: search_products and get_product. */
class LiteServer extends Server
{
    protected function boot(): void
    {
        $this->name = (string) config('storewire-lite.server.name', $this->name);
        $this->version = (string) config('storewire-lite.server.version', $this->version);
        $this->instructions = (string) config('storewire-lite.server.instructions', $this->instructions);
        $this->tools = [SearchProductsTool::class, GetProductTool::class];
        $this->resources = [];
        $this->prompts = [];
    }
}
