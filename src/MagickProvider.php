<?php

namespace LaravelMagick;

use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use Laravel\Nova\Events\ServingNova;
use Laravel\Nova\Nova;
use Laravel\Nova\NovaCoreServiceProvider;
use RuntimeException;
use LaravelMagick\MediaTransformer\MediaTransformer;
use LaravelMagick\UrlHandler\UrlHandler;

class MagickProvider extends ServiceProvider
{
    public function boot(Router $router)
    {
        // setup configuration, merge values from top level
        $packageConfigPath = realpath(__DIR__ . '/../config/magick.php');
        $this->mergeConfigFrom($packageConfigPath, 'magick');

        // publish the configuration in cli local environment
        if ($this->app->runningInConsole() && $this->app->environment('local')) {
            $this->publishes([$packageConfigPath => config_path('magick.php')], 'config');
        }

        if (config('magick.render.enable')) {
            if (!$this->app->runningInConsole() && !extension_loaded('imagick')) {
                throw new RuntimeException('Laravel Magick requires ext/ImageMagick in order to render images');
            }

            $prefixes = config('magick.filesystem.disk_render_prefixes');

            foreach ($prefixes as $disk => $prefix) {
                $router->get("{$prefix}/{path}", Controllers\MagickController::class . '@render')
                    ->where('path', '(.*)')
                    ->name('magick.render.' . $disk)
                    ->defaults('disk', $disk)
                    ->domain(config('magick.render.domain', null));
            }

            Blade::directive('placeholderImageUrl', [View\BladeDirectives::class, 'placeholderImageUrl']);
        }

        if ($this->app->getProviders(NovaCoreServiceProvider::class)) {
            Nova::serving(function (ServingNova $event) {
                Nova::script('magick', __DIR__ . '/../dist/nova.js');
            });
        }
    }

    public function register()
    {
        $this->app->singleton(MediaTransformer::class, function ($app) {
            return new MediaTransformer(MediaTransformer::createTransformationCollection(
                config('magick.render.transformation.transformers', [])
            ));
        });

        $this->app->singleton(UrlHandler::class, function ($app) {
            return new UrlHandler(UrlHandler::createStrategy(
                config('magick.urls.strategy', 'legacy')
            ));
        });
    }
}
