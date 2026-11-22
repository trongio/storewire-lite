# Storewire Lite

[![mcptry](https://mcptry.com/badge/io.github.trongio/storewire-lite)](https://mcptry.com/servers/io.github.trongio/storewire-lite)

Let AI assistants read your Laravel store's catalogue. Storewire Lite adds a read-only
[Model Context Protocol](https://modelcontextprotocol.io) server to your app, built on the official
[`laravel/mcp`](https://github.com/laravel/mcp), with two tools:

| Tool | What a shopper's assistant gets |
|---|---|
| `search_products` | Search by words or model number, category, brand, price range, in-stock only and spec filters; sorted; paged. Each hit is a product card (page URL, photo, price, stock line, key specs, category), plus the total, a link to the same results on your site, suggested filters and "did you mean" when nothing matched. |
| `get_product` | One product as its page shows it: photos, price, stock, the spec table, warranty, plain-text description, documents, the category trail, similar products and what goes with it. Found by id, URL, slug or model number. |

You implement one interface over your own models. Nothing is written, nothing is ordered, nothing leaves your
catalogue except what a visitor could already see.

Free and MIT licensed.

See the full Storewire running on a demo store: https://demo.plugins.hackerman.ge (point an assistant that supports remote MCP servers at `https://demo.plugins.hackerman.ge/mcp`).

## Install

```bash
composer require hackerman/storewire-lite
php artisan vendor:publish --tag=storewire-lite-config
```

Requirements: PHP 8.3+, Laravel 12.41+ or 13, `laravel/mcp` 1.2+.

## Connect your catalogue

Implement `Hackerman\StorewireLite\Contracts\CatalogRepository` and bind it:

```php
use Hackerman\StorewireLite\Contracts\CatalogRepository;

$this->app->singleton(CatalogRepository::class, App\Mcp\MyCatalog::class);
```

```php
interface CatalogRepository
{
    // An id from an earlier answer, a product URL or slug, a model number or SKU; null when nothing visible matches.
    public function resolve(string $reference, string $locale): ?string;

    // One product as its page shows it; null when a visitor may not see it.
    public function product(string $id, string $locale): ?ProductDetail;

    public function search(SearchQuery $query): SearchResult;
}
```

The data objects (`ProductCard`, `ProductDetail`, `SearchQuery`, `SearchResult`, `Price`, `Availability` and
friends) are small readonly classes in `Hackerman\StorewireLite\Data`. Give each card its guest price; a card
without one answers with the amount null.

Then switch the server on:

```dotenv
STOREWIRE_LITE_ENABLED=true
STOREWIRE_LITE_SERVER_NAME="Your Store"
STOREWIRE_LITE_CURRENCY=USD
```

It answers `POST /mcp` (Streamable HTTP, throttled per client) and runs over stdio with
`php artisan mcp:start storewire-lite`. Try it:

```bash
curl -s http://localhost:8000/mcp \
  -H 'Content-Type: application/json' -H 'Accept: application/json, text/event-stream' \
  -H 'MCP-Protocol-Version: 2025-06-18' \
  -d '{"jsonrpc":"2.0","id":1,"method":"tools/call","params":{"name":"search_products","arguments":{"query":"tent"}}}'
```

## Configuration

`config/storewire-lite.php`: the switch, path, server name and instructions, currency, answer locales, your
search page URL (`search_url`, with an optional `{query}` placeholder), the per-minute budget per client, and the
answer size limits.

## Storewire Lite or Storewire

Lite lets an assistant find and describe your products. [Storewire](https://plugins.hackerman.ge) lets it sell
them: the shopper goes from a question to a filled cart on your own site without leaving the conversation.

| | Lite (free) | Storewire |
|---|:---:|:---:|
| `search_products`, `get_product` | yes | yes |
| Cart handoff: `cart_add_item`, `cart_update_item`, `cart_remove_item`, `cart_view`, with a `cart_url` that opens your own cart page with the products in it | | yes |
| `compare_products` side by side, with the better value marked | | yes |
| `check_compatibility` (does this fit that, what goes with this) | | yes |
| `list_categories`, `find_documents` (manuals, datasheets) | | yes |
| `get_store_info` (stores, hours, payment, returns, FAQ) and `get_delivery_options` (fees, free-delivery threshold, same day) | | yes |
| Interactive widgets that draw product cards, comparisons and the cart inside the assistant (MCP Apps) | | yes |
| `storewire:manifest`: the app manifest assistant directories ask for | | yes |
| Response size budget, per-tool throttles, call metrics, translatable answers, your own tools on the same server | | yes |

Lite's `CatalogRepository` is a subset of Storewire's: same method signatures, same data objects. Upgrading
means switching the namespace and adding the remaining methods; nothing you wrote for Lite is thrown away.

See it working and get Storewire at **[plugins.hackerman.ge](https://plugins.hackerman.ge)**.

## Testing

```bash
composer test
composer analyse
```

## License

MIT. See [LICENSE](LICENSE).
