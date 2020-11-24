<?php
namespace DreamFactory\Core\DreamFactoryPrometheusExporter;

use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;
use Prometheus\CollectorRegistry;
use Prometheus\RenderTextFormat;
use Prometheus\Storage\Redis;
use Spatie\HttpLogger\Middlewares\HttpLogger;

class ServiceProvider extends \Illuminate\Support\ServiceProvider
{

    const MIDDLEWARE_NAME = 'df.http_logger';

    public function boot()
    {
        dd('Hello world');
        if (env('PROMETHEUS_ENABLED') != 'true' && env('CACHE_DRIVER') != 'redis') {
            return;
        }

        Redis::setDefaultOptions(
            [
                'host' => env('CACHE_HOST', '127.0.0.1'),
                'port' => env('CACHE_PORT', 6379),
                'password' => env('CACHE_PASSWORD', null),
                'timeout' => 0.1, // in seconds
                'read_timeout' => '10', // in seconds
                'persistent_connections' => false
            ]
        );

        $configPath = __DIR__ . '/../config/http-logger.php';
        if (function_exists('config_path')) {
            $publishPath = config_path('http-logger.php');
        } else {
            $publishPath = base_path('config/http-logger.php');
        }
        $this->publishes([$configPath => $publishPath], 'config');
        // add migrations
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        $this->addMiddleware();
        $this->addMetricRoute();
    }

    public function register()
    {
        if (env('PROMETHEUS_ENABLED') != 'true') {
            return;
        }

        $this->mergeConfigFrom(__DIR__ . '/../config/http-logger.php', 'http-logger');
    }

    /**
     * Register any middleware aliases.
     *
     * @return void
     */
    protected function addMiddleware()
    {
        // the method name was changed in Laravel 5.4
        if (method_exists(Router::class, 'aliasMiddleware')) {
            Route::aliasMiddleware(ServiceProvider::MIDDLEWARE_NAME, HttpLogger::class);
        } else {
            /** @noinspection PhpUndefinedMethodInspection */
            Route::middleware(ServiceProvider::MIDDLEWARE_NAME, HttpLogger::class);
        }

        Route::pushMiddlewareToGroup('df.api', ServiceProvider::MIDDLEWARE_NAME);
    }

    protected function addMetricRoute() {
        Route::get(env('PROMETHEUS_TELEMETRY', '/metrics'), function () {
            $renderer = new RenderTextFormat();
            return $renderer->render(CollectorRegistry::getDefault()->getMetricFamilySamples());
        });
    }
}
