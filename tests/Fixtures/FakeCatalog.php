<?php

declare(strict_types=1);

namespace Hackerman\StorewireLite\Tests\Fixtures;

use Hackerman\StorewireLite\Contracts\CatalogRepository;
use Hackerman\StorewireLite\Data\Availability;
use Hackerman\StorewireLite\Data\Category;
use Hackerman\StorewireLite\Data\DocumentFile;
use Hackerman\StorewireLite\Data\FilterGroup;
use Hackerman\StorewireLite\Data\Image;
use Hackerman\StorewireLite\Data\Link;
use Hackerman\StorewireLite\Data\Price;
use Hackerman\StorewireLite\Data\ProductCard;
use Hackerman\StorewireLite\Data\ProductDetail;
use Hackerman\StorewireLite\Data\RelatedProduct;
use Hackerman\StorewireLite\Data\SearchQuery;
use Hackerman\StorewireLite\Data\SearchResult;
use Hackerman\StorewireLite\Data\Spec;
use Hackerman\StorewireLite\Data\SpecGroup;
use Hackerman\StorewireLite\Data\StockState;
use Hackerman\StorewireLite\Data\Warranty;

/**
 * A small in-memory lamp shop. HID1 is unpublished: no answer may ever
 * name it. Every SearchQuery it is asked is kept in {@see self::$searches}.
 */
final class FakeCatalog implements CatalogRepository
{
    /** @var array<string, array{name: string, slug: string, model: string, price: ?float, stock: StockState, visible: bool}> */
    public const array PRODUCTS = [
        'P1' => ['name' => 'Desk lamp', 'slug' => 'desk-lamp', 'model' => 'DL-100', 'price' => 25.0, 'stock' => StockState::InStock, 'visible' => true],
        'P2' => ['name' => 'Floor lamp', 'slug' => 'floor-lamp', 'model' => 'FL-200', 'price' => 80.0, 'stock' => StockState::LowStock, 'visible' => true],
        'P3' => ['name' => 'Pendant lamp', 'slug' => 'pendant-lamp', 'model' => 'PL-300', 'price' => null, 'stock' => StockState::OnRequest, 'visible' => true],
        'P4' => ['name' => 'LED bulb', 'slug' => 'led-bulb', 'model' => 'B-27', 'price' => 4.5, 'stock' => StockState::InStock, 'visible' => true],
        'HID1' => ['name' => 'Prototype lamp', 'slug' => 'prototype-lamp', 'model' => 'PROTO-1', 'price' => 999.0, 'stock' => StockState::InStock, 'visible' => false],
    ];

    /** @var list<SearchQuery> */
    public array $searches = [];

    public function resolve(string $reference, string $locale): ?string
    {
        $needle = strtolower(basename((string) (parse_url($reference, PHP_URL_PATH) ?: $reference)));

        foreach (self::PRODUCTS as $id => $product) {
            if ($product['visible'] && in_array($needle, [strtolower($id), $product['slug'], strtolower($product['model'])], true)) {
                return $id;
            }
        }

        return null;
    }

    public function product(string $id, string $locale): ?ProductDetail
    {
        $card = $this->card($id);

        if ($card === null) {
            return null;
        }

        $bulb = $this->card('P4');

        return new ProductDetail(
            card: $card,
            images: [new Image('/media/'.$id.'.jpg', '/media/'.$id.'-thumb.jpg'), new Image('https://cdn.shop.test/'.$id.'-side.jpg', 'https://cdn.shop.test/'.$id.'-side-thumb.jpg', 'Side view')],
            description: '<p>A <b>steady</b> light.</p><ul><li>Dimmable</li></ul>',
            warranty: new Warranty(24, '2-year warranty'),
            specs: [new SpecGroup('Light', [new Spec('Colour temperature', '2700 K', 'kelvin'), new Spec('Dimmable', 'Yes')])],
            documents: [new DocumentFile('D1', 'manual', 'User manual', '/files/'.$id.'-manual.pdf')],
            breadcrumb: [new Link('Lamps', 'https://shop.test/lamps')],
            similar: $this->cards([$id === 'P1' ? 'P2' : 'P1']),
            goesWith: $bulb === null || $id === 'P4' ? [] : [new RelatedProduct($bulb, 'requires')],
        );
    }

    public function search(SearchQuery $query): SearchResult
    {
        $this->searches[] = $query;
        $words = $query->text === null ? [] : explode(' ', strtolower($query->text));
        $hits = [];

        foreach (array_keys(self::PRODUCTS) as $id) {
            $card = $this->card($id);
            $haystack = strtolower($card === null ? '' : $card->name.' '.$card->model);

            if ($card !== null && array_filter($words, static fn (string $word): bool => ! str_contains($haystack, $word)) === []) {
                $hits[] = $card;
            }
        }

        if ($hits === []) {
            return new SearchResult([], 0, $query->page, $query->perPage,
                didYouMeanCategories: [new Category('Lamps', 'lamps', 'https://shop.test/lamps', 3)],
                didYouMeanProducts: $this->cards(['P1']));
        }

        return new SearchResult(
            products: array_slice($hits, ($query->page - 1) * $query->perPage, $query->perPage),
            total: count($hits),
            page: $query->page,
            perPage: $query->perPage,
            suggestedFilters: [
                new FilterGroup('category', 'Category', [['value' => 'lamps', 'label' => 'Lamps', 'count' => 3], ['value' => 'garden', 'label' => 'Garden', 'count' => 0]]),
            ],
            appliedFilters: $query->filters === [] ? [] : [['code' => 'color', 'label' => 'Colour: black']],
        );
    }

    /**
     * @param  list<string>  $ids
     * @return list<ProductCard>
     */
    private function cards(array $ids): array
    {
        $cards = [];

        foreach ($ids as $id) {
            $card = $this->card($id);

            if ($card !== null) {
                $cards[] = $card;
            }
        }

        return $cards;
    }

    private function card(string $id): ?ProductCard
    {
        $product = self::PRODUCTS[$id] ?? null;

        if ($product === null || ! $product['visible']) {
            return null;
        }

        return new ProductCard(
            id: $id,
            name: $product['name'],
            url: 'https://shop.test/products/'.$product['slug'],
            availability: new Availability($product['stock'], $product['stock'] === StockState::LowStock ? 'Only 2 left' : 'In stock'),
            price: $product['price'] === null ? Price::onRequest('USD') : new Price($product['price'], 'USD'),
            brand: 'Lumen',
            model: $product['model'],
            image: new Image('/media/'.$product['slug'].'.jpg', '/media/'.$product['slug'].'-thumb.jpg'),
            keySpecs: [new Spec('Colour temperature', '2700 K', 'kelvin')],
            badges: [],
            category: new Link('Lamps', 'https://shop.test/lamps'),
        );
    }
}
