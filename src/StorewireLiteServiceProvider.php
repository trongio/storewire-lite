<?php

declare(strict_types=1);

namespace Hackerman\StorewireLite;

use Hackerman\StorewireLite\Server\LiteServer;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Laravel\Mcp\Facades\Mcp;

final class StorewireLiteServiceProvider extends ServiceProvider
{
    public const string LIMITER = 'storewire-lite';

    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/storewire-lite.php', 'storewire-lite');
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/storewire-lite.php' => config_path('storewire-lite.php'),
            ], 'storewire-lite-config');
        }

        RateLimiter::for(self::LIMITER, static fn (Request $request): Limit => Limit::perMinute(max(1, (int) config('storewire-lite.per_minute', 60)))
            ->by('storewire-lite:'.($request->ip() ?? 'none')));

        if ((bool) config('storewire-lite.enabled')) {
            $this->registerRoutes();
        }
    }

    private function registerRoutes(): void
    {
        $handle = config('storewire-lite.handle');

        Mcp::local(is_string($handle) && $handle !== '' ? $handle : 'storewire-lite', LiteServer::class);
        Mcp::web((string) config('storewire-lite.path', 'mcp'), LiteServer::class)
            ->middleware(['throttle:'.self::LIMITER, ...(array) config('storewire-lite.middleware')])
            ->name('storewire-lite');
    }
}
