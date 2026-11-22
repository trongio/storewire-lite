<?php

declare(strict_types=1);

it('describes a product found by its URL', function (): void {
    $product = $this->callTool('get_product', ['product' => 'https://shop.test/products/desk-lamp'])->json('result.structuredContent');

    expect($product['public_id'])->toBe('P1')
        ->and($product['description'])->toBe('A steady light. Dimmable')
        ->and($product['images'][0]['url'])->toBe('https://shop.test/media/P1.jpg')
        ->and($product['images'][0]['alt'])->toBe('Desk lamp')
        ->and($product['images'][1]['url'])->toBe('https://cdn.shop.test/P1-side.jpg')
        ->and($product['specs'][0]['attributes'])->toBe([
            ['code' => 'kelvin', 'label' => 'Colour temperature', 'value' => '2700 K'],
            ['code' => '', 'label' => 'Dimmable', 'value' => 'Yes'],
        ])
        ->and($product['documents'][0]['download_url'])->toBe('https://shop.test/files/P1-manual.pdf')
        ->and($product['warranty'])->toBe(['months' => 24, 'text' => '2-year warranty'])
        ->and(array_column($product['similar'], 'public_id'))->toBe(['P2'])
        ->and($product['goes_with'][0])->toMatchArray(['public_id' => 'P4', 'relation' => 'requires']);
});

it('finds a product by model number and shows a price on request', function (): void {
    $product = $this->callTool('get_product', ['product' => 'pl-300'])->json('result.structuredContent');

    expect($product['public_id'])->toBe('P3')
        ->and($product['price'])->toMatchArray(['amount' => null, 'on_request' => true]);
});

it('says when nothing matches, an unpublished product included', function (string $reference): void {
    $response = $this->callTool('get_product', ['product' => $reference]);

    expect($response->json('result.isError'))->toBeTrue()
        ->and($response->json('result.content.0.text'))->toContain('search_products');
})->with(['nothing-here', 'HID1', 'prototype-lamp', 'too-long' => str_repeat('x', 300)]);
