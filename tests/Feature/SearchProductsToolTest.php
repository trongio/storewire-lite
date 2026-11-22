<?php

declare(strict_types=1);

use Hackerman\StorewireLite\Contracts\CatalogRepository;
use Hackerman\StorewireLite\Data\SortOrder;

it('answers a query with product cards and the store search URL', function (): void {
    $found = $this->callTool('search_products', ['query' => 'floor'])->json('result.structuredContent');

    expect($found['total'])->toBe(1)
        ->and($found['products'][0])->toMatchArray([
            'public_id' => 'P2',
            'name' => 'Floor lamp',
            'url' => 'https://shop.test/products/floor-lamp',
            'price' => ['amount' => 80, 'compare_at' => null, 'currency' => 'USD', 'vat_included' => true, 'on_request' => false],
            'availability' => ['state' => 'low_stock', 'label' => 'Only 2 left', 'branches' => []],
        ])
        ->and($found['products'][0]['image']['url'])->toBe('https://shop.test/media/floor-lamp.jpg')
        ->and($found['products'][0]['image']['alt'])->toBe('Floor lamp')
        ->and($found['url'])->toBe('https://shop.test/search?q=floor')
        ->and($found['did_you_mean'])->toBeNull();
});

it('reads loose arguments into the SearchQuery', function (): void {
    $this->callTool('search_products', [
        'query' => '  lamp  ',
        'category' => 'https://shop.test/lamps/',
        'price_min' => '-5',
        'price_max' => '100',
        'in_stock' => 'yes',
        'filters' => ['color' => 'black', '' => ['x']],
        'sort' => 'price_asc',
        'page' => '2',
        'limit' => 999,
    ]);

    $query = app(CatalogRepository::class)->searches[0];

    expect($query->text)->toBe('lamp')
        ->and($query->category)->toBe('lamps')
        ->and($query->priceMin)->toBe(0.0)
        ->and($query->priceMax)->toBe(100.0)
        ->and($query->inStock)->toBeTrue()
        ->and($query->filters)->toBe(['color' => ['black']])
        ->and($query->sort)->toBe(SortOrder::PriceAsc)
        ->and($query->page)->toBe(2)
        ->and($query->perPage)->toBe(20);
});

it('keeps only filter values that leave something', function (): void {
    $found = $this->callTool('search_products', ['query' => 'lamp'])->json('result.structuredContent');

    expect(array_column($found['suggested_filters'][0]['options'], 'value'))->toBe(['lamps']);
});

it('rescues an empty search with the store suggestions', function (): void {
    $found = $this->callTool('search_products', ['query' => 'sofa'])->json('result.structuredContent');

    expect($found['total'])->toBe(0)
        ->and($found['did_you_mean']['categories'][0]['slug'])->toBe('lamps')
        ->and($found['did_you_mean']['products'][0]['public_id'])->toBe('P1');
});

it('asks for something to search by', function (): void {
    $response = $this->callTool('search_products', []);

    expect($response->json('result.isError'))->toBeTrue()
        ->and($response->json('result.content.0.text'))->toContain('query, category or brand');
});

it('refuses an unknown sort', function (): void {
    expect($this->callTool('search_products', ['query' => 'lamp', 'sort' => 'cheapest'])->json('result.isError'))->toBeTrue();
});

it('never names an unpublished product', function (): void {
    expect($this->callTool('search_products', ['query' => 'prototype'])->json('result.structuredContent.total'))->toBe(0);
});
