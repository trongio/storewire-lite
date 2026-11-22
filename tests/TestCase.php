<?php

declare(strict_types=1);

namespace Hackerman\StorewireLite\Tests;

use Hackerman\StorewireLite\Contracts\CatalogRepository;
use Hackerman\StorewireLite\StorewireLiteServiceProvider;
use Hackerman\StorewireLite\Tests\Fixtures\FakeCatalog;
use Illuminate\Testing\TestResponse;
use Laravel\Mcp\Server\McpServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    public const string PROTOCOL = '2025-06-18';

    /**
     * @return list<class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [McpServiceProvider::class, StorewireLiteServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('app.key', 'base64:'.base64_encode(str_repeat('k', 32)));
        $app['config']->set('app.url', 'https://shop.test');
        $app['config']->set('storewire-lite.enabled', true);
        $app['config']->set('storewire-lite.server.name', 'Lamp Shop');
        $app['config']->set('storewire-lite.search_url', 'https://shop.test/search');
        $app->singleton(CatalogRepository::class, FakeCatalog::class);
    }

    /**
     * One JSON-RPC request to the HTTP endpoint, as an MCP client sends it.
     *
     * @param  array<string, mixed>  $params
     * @return TestResponse<\Symfony\Component\HttpFoundation\Response>
     */
    protected function rpc(string $method, array $params = []): TestResponse
    {
        return $this->withHeaders([
            'Accept' => 'application/json, text/event-stream',
            'MCP-Protocol-Version' => self::PROTOCOL,
        ])->postJson('/mcp', array_filter([
            'jsonrpc' => '2.0',
            'id' => 1,
            'method' => $method,
            'params' => $params === [] ? null : $params,
        ], static fn (mixed $value): bool => $value !== null));
    }

    /**
     * @param  array<string, mixed>  $arguments
     * @return TestResponse<\Symfony\Component\HttpFoundation\Response>
     */
    protected function callTool(string $name, array $arguments = []): TestResponse
    {
        return $this->rpc('tools/call', ['name' => $name, 'arguments' => (object) $arguments])->assertOk();
    }
}
