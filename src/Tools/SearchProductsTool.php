<?php

declare(strict_types=1);

namespace Hackerman\StorewireLite\Tools;

use Hackerman\StorewireLite\Contracts\CatalogRepository;
use Hackerman\StorewireLite\Data\Category;
use Hackerman\StorewireLite\Data\FilterGroup;
use Hackerman\StorewireLite\Data\ProductCard;
use Hackerman\StorewireLite\Data\SearchQuery;
use Hackerman\StorewireLite\Data\SearchResult;
use Hackerman\StorewireLite\Data\SortOrder;
use Hackerman\StorewireLite\Support\Cards;
use Hackerman\StorewireLite\Support\Input;
use Hackerman\StorewireLite\Support\Locale;
use Hackerman\StorewireLite\Support\PublicText;
use Hackerman\StorewireLite\Support\Urls;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Name;

/**
 * The catalogue search ({@see CatalogRepository::search()}): the hits as
 * product cards, the total, the same results on the store's website, the
 * filters a shopper could narrow by next and, when nothing matched, the
 * store's suggestions.
 */
#[Name('search_products')]
class SearchProductsTool extends LiteTool
{
    public const int MAX_FILTERS = 6;

    public const int MAX_FILTER_VALUES = 8;

    public const int DEFAULT_LIMIT = 10;

    public const int MAX_SUGGESTED = 6;

    public const int MAX_OPTIONS = 8;

    public function title(): string
    {
        return 'Search products';
    }

    public function description(): string
    {
        return 'Search the '.self::store().' catalogue the way its search page does: by words or a model number, within a category or brand, by price in '.Cards::currency().', in stock only, and by spec filters. Each product comes with its page URL, photo, price, stock line, key specs and category. The answer also gives the total, the same results\' URL on the store\'s website, suggested_filters and, when nothing matches, did_you_mean.';
    }

    public function handle(Request $request, CatalogRepository $catalog): Response|ResponseFactory
    {
        $locale = Locale::from($request);
        $text = PublicText::argument($request->get('query'));
        $category = PublicText::argument($request->get('category'));
        $brand = PublicText::argument($request->get('brand'));

        if ($text === null && $category === null && $brand === null) {
            return Response::error('Give query, category or brand.');
        }

        $sort = Input::string($request->get('sort'));

        if ($sort !== null && SortOrder::tryFrom($sort) === null) {
            return Response::error('sort is one of: '.implode(', ', array_column(SortOrder::cases(), 'value')).'.');
        }

        $min = Input::number($request->get('price_min'));
        $max = Input::number($request->get('price_max'));

        $query = new SearchQuery(
            locale: $locale,
            text: $text,
            category: $category === null ? null : trim((string) (str_contains($category, '/') ? parse_url($category, PHP_URL_PATH) : $category), '/'),
            brand: $brand,
            priceMin: $min === null ? null : max(0.0, $min),
            priceMax: $max === null ? null : max(0.0, $max),
            inStock: Input::bool($request->get('in_stock')) === true,
            filters: self::filters($request->get('filters')),
            sort: $sort === null ? SortOrder::Relevance : SortOrder::from($sort),
            page: Input::positive($request->get('page')),
            perPage: Input::limit($request->get('limit'), self::DEFAULT_LIMIT, self::limit('results_max', 20)),
        );

        return Response::structured(self::present($catalog->search($query), $query));
    }

    /**
     * @return array<string, mixed>
     */
    protected function arguments(JsonSchema $schema): array
    {
        $currency = Cards::currency();

        return [
            'query' => $schema->string()->description('Words or a model number. Required unless category or brand is given.'),
            'category' => $schema->string()->description('A category slug, e.g. from suggested_filters.'),
            'brand' => $schema->string()->description('A brand name.'),
            'price_min' => $schema->number()->min(0)->description("Lowest price in {$currency}."),
            'price_max' => $schema->number()->min(0)->description("Highest price in {$currency}."),
            'in_stock' => $schema->boolean()->description('Only products in stock now.'),
            'filters' => $schema->object()->description('Spec filters from suggested_filters: code => list of values; a numeric spec also takes "min~max".'),
            'sort' => $schema->string()->enum(array_column(SortOrder::cases(), 'value'))->description('Order: relevance (default), price_asc, price_desc or newest.'),
            'page' => $schema->integer()->min(1)->description('Page number (default 1).'),
            'limit' => $schema->integer()->min(1)->max(self::limit('results_max', 20))->description('Products per page (default '.self::DEFAULT_LIMIT.').'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function outputSchema(JsonSchema $schema): array
    {
        return [
            'products' => $schema->array()->items(Cards::schema($schema))->required(),
            'total' => $schema->integer()->description('All matching products, across pages.')->required(),
            'page' => $schema->integer()->required(),
            'per_page' => $schema->integer()->required(),
            'url' => $schema->string()->description('These results on the store\'s website.')->required(),
            'suggested_filters' => $schema->array()->items($schema->object([
                'code' => $schema->string()->description('"category": pass a value as the category argument; any other code: pass values in filters[code].'),
                'label' => $schema->string(),
                'options' => $schema->array()->items($schema->object([
                    'value' => $schema->string(),
                    'label' => $schema->string(),
                    'count' => $schema->integer()->description('Products this value would leave.'),
                ])),
            ]))->required(),
            'applied_filters' => $schema->array()->items($schema->object([
                'code' => $schema->string(),
                'label' => $schema->string(),
            ]))->required(),
            'did_you_mean' => $schema->object([
                'categories' => $schema->array()->items($schema->object([
                    'name' => $schema->string(),
                    'slug' => $schema->string(),
                    'url' => $schema->string()->nullable(),
                    'product_count' => $schema->integer(),
                ])),
                'products' => $schema->array()->items(Cards::schema($schema)),
            ])->nullable()->description('Only when nothing matched: what the store suggests instead.')->required(),
        ];
    }

    /**
     * @return array<string, list<string>>
     */
    private static function filters(mixed $filters): array
    {
        if (! is_array($filters)) {
            return [];
        }

        $pairs = [];

        foreach (array_slice($filters, 0, self::MAX_FILTERS, preserve_keys: true) as $code => $values) {
            $given = array_slice(Input::list($values), 0, self::MAX_FILTER_VALUES);
            $list = array_values(array_filter(array_map(PublicText::argument(...), $given), is_string(...)));

            if (is_string($code) && $code !== '' && $list !== []) {
                $pairs[mb_substr($code, 0, PublicText::ECHO_CHARS)] = $list;
            }
        }

        return $pairs;
    }

    /**
     * @return array<string, mixed>
     */
    private static function present(SearchResult $result, SearchQuery $query): array
    {
        $rescue = $result->didYouMeanCategories !== [] || $result->didYouMeanProducts !== [];
        $rows = static fn (array $cards): array => array_map(static fn (ProductCard $card): array => Cards::row($card), $cards);

        return [
            'products' => $rows(array_slice($result->products, 0, $query->perPage)),
            'total' => $result->total,
            'page' => $result->page,
            'per_page' => $result->perPage,
            'url' => Urls::searchPage($query),
            'suggested_filters' => array_slice(array_values(array_filter(array_map(self::filterGroup(...), $result->suggestedFilters))), 0, self::MAX_SUGGESTED),
            'applied_filters' => $result->appliedFilters,
            'did_you_mean' => ! $rescue ? null : [
                'categories' => array_map(static fn (Category $category): array => [
                    'name' => $category->name,
                    'slug' => $category->slug,
                    'url' => $category->url,
                    'product_count' => $category->productCount,
                ], $result->didYouMeanCategories),
                'products' => $rows($result->didYouMeanProducts),
            ],
        ];
    }

    /**
     * @return array{code: string, label: string, options: list<array{value: string, label: string, count: int}>}|null
     */
    private static function filterGroup(FilterGroup $group): ?array
    {
        $options = array_values(array_filter($group->options, static fn (array $option): bool => $option['count'] > 0));

        return $options === [] ? null : ['code' => $group->code, 'label' => $group->label, 'options' => array_slice($options, 0, self::MAX_OPTIONS)];
    }
}
