<?php

declare(strict_types=1);

namespace Hackerman\StorewireLite\Tools;

use Hackerman\StorewireLite\Contracts\CatalogRepository;
use Hackerman\StorewireLite\Data\DocumentFile;
use Hackerman\StorewireLite\Data\Image;
use Hackerman\StorewireLite\Data\Link;
use Hackerman\StorewireLite\Data\ProductCard;
use Hackerman\StorewireLite\Data\ProductDetail;
use Hackerman\StorewireLite\Data\RelatedProduct;
use Hackerman\StorewireLite\Data\Spec;
use Hackerman\StorewireLite\Data\SpecGroup;
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
 * One product as its page shows it ({@see CatalogRepository::product()}):
 * its card, photos, plain-text description, warranty, spec table,
 * documents, category trail, `similar` and `goes_with`.
 */
#[Name('get_product')]
class GetProductTool extends LiteTool
{
    public function title(): string
    {
        return 'Get product details';
    }

    public function description(): string
    {
        return 'One product as its page on the '.self::store().' website shows it: name, brand, model, page URL, photos, price, stock line, key specs, the full spec table by group, warranty, a plain-text description, documents with download links, and its category trail. similar lists alternatives; goes_with lists what it needs or works with.';
    }

    public function handle(Request $request, CatalogRepository $catalog): Response|ResponseFactory
    {
        $locale = Locale::from($request);
        $reference = Input::string($request->get('product'));
        $id = $reference === null || mb_strlen($reference) > PublicText::INPUT_CHARS ? null : $catalog->resolve($reference, $locale);
        $product = $id === null ? null : $catalog->product($id, $locale);

        return $product === null ? self::notFound() : Response::structured(self::present($product));
    }

    /**
     * @return array<string, mixed>
     */
    protected function arguments(JsonSchema $schema): array
    {
        return [
            'product' => $schema->string()->description('The product: its public_id from an earlier answer, its page URL or slug on the store\'s website, or the manufacturer model number.')->required(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function outputSchema(JsonSchema $schema): array
    {
        return [
            ...Cards::properties($schema),
            'images' => $schema->array()->items(Cards::imageSchema($schema))->required(),
            'description' => $schema->string()->nullable()->description('Plain text, shortened.')->required(),
            'warranty' => $schema->object([
                'months' => $schema->integer(),
                'text' => $schema->string()->nullable(),
            ])->nullable()->required(),
            'specs' => $schema->array()->items($schema->object([
                'name' => $schema->string()->nullable(),
                'attributes' => $schema->array()->items($schema->object([
                    'code' => $schema->string(),
                    'label' => $schema->string(),
                    'value' => $schema->string(),
                ])),
            ]))->required(),
            'documents' => $schema->array()->items($schema->object([
                'public_id' => $schema->string(),
                'type' => $schema->string(),
                'title' => $schema->string()->nullable(),
                'language' => $schema->string()->nullable(),
                'version' => $schema->string()->nullable(),
                'size' => $schema->string()->nullable(),
                'download_url' => $schema->string(),
                'open_url' => $schema->string()->nullable(),
                'external' => $schema->boolean(),
            ]))->required(),
            'breadcrumb' => $schema->array()->items($schema->object([
                'name' => $schema->string(),
                'url' => $schema->string()->nullable(),
            ]))->required(),
            'similar' => $schema->array()->items(Cards::schema($schema))->required(),
            'goes_with' => $schema->array()->items($schema->object([
                ...Cards::properties($schema),
                'relation' => $schema->string()->description('How it relates, e.g. requires, accessory, works with.'),
            ]))->required(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function present(ProductDetail $product): array
    {
        $rail = self::limit('rail', 6);

        return [
            ...Cards::row($product->card),
            'images' => array_map(static fn (Image $image): array => [
                'url' => Urls::media($image->url),
                'thumb_url' => Urls::media($image->thumbUrl),
                'alt' => $image->alt !== '' ? $image->alt : $product->card->name,
            ], array_slice($product->images, 0, self::limit('images', 4))),
            'description' => PublicText::plain($product->description),
            'warranty' => $product->warranty === null ? null : [
                'months' => $product->warranty->months,
                'text' => PublicText::plain($product->warranty->text),
            ],
            'specs' => array_map(static fn (SpecGroup $group): array => [
                'name' => $group->name,
                'attributes' => array_map(static fn (Spec $spec): array => [
                    'code' => $spec->code ?? '',
                    'label' => $spec->label,
                    'value' => (string) PublicText::plain($spec->value),
                ], $group->specs),
            ], $product->specs),
            'documents' => array_map(static fn (DocumentFile $file): array => [
                ...$file->toArray(),
                'download_url' => Urls::media($file->downloadUrl),
                'open_url' => $file->openUrl === null ? null : Urls::media($file->openUrl),
            ], $product->documents),
            'breadcrumb' => array_map(static fn (Link $link): array => $link->toArray(), $product->breadcrumb),
            'similar' => array_map(static fn (ProductCard $card): array => Cards::row($card), array_slice($product->similar, 0, $rail)),
            'goes_with' => array_map(static fn (RelatedProduct $related): array => [
                ...Cards::row($related->card),
                'relation' => $related->relation,
            ], array_slice($product->goesWith, 0, $rail)),
        ];
    }
}
