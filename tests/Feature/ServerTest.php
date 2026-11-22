<?php

declare(strict_types=1);

use Hackerman\StorewireLite\StorewireLiteServiceProvider;

it('answers initialize with the configured store name', function (): void {
    $response = $this->rpc('initialize', [
        'protocolVersion' => self::PROTOCOL,
        'capabilities' => (object) [],
        'clientInfo' => ['name' => 'pest', 'version' => '1.0'],
    ])->assertOk();

    expect($response->json('result.serverInfo.name'))->toBe('Lamp Shop')
        ->and($response->json('result.instructions'))->toContain('cannot place orders');
});

it('lists exactly the two read-only tools', function (): void {
    $tools = $this->rpc('tools/list')->assertOk()->json('result.tools');

    expect(array_column($tools, 'name'))->toBe(['search_products', 'get_product']);

    foreach ($tools as $tool) {
        expect($tool['annotations'])->toMatchArray(['readOnlyHint' => true, 'destructiveHint' => false])
            ->and($tool['inputSchema']['properties'])->toHaveKey('locale')
            ->and($tool['description'])->toContain('Lamp Shop');
    }
});

it('registers no route until it is switched on', function (): void {
    config()->set('storewire-lite.enabled', false);
    app('router')->setRoutes(new Illuminate\Routing\RouteCollection);

    (new StorewireLiteServiceProvider(app()))->boot();

    expect(app('router')->getRoutes()->getByName('storewire-lite'))->toBeNull();
});

it('throttles per client', function (): void {
    config()->set('storewire-lite.per_minute', 2);

    $this->rpc('tools/list')->assertOk();
    $this->rpc('tools/list')->assertOk();
    $this->rpc('tools/list')->assertStatus(429);
});
