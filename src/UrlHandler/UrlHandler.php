<?php

namespace LaravelMagick\UrlHandler;

use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use InvalidArgumentException;
use LaravelMagick\Eloquent\AbstractMedia;

class UrlHandler
{
    const BUILTIN_STRATEGIES = [
        'legacy' => Strategies\LegacyStrategy::class
    ];

    protected $strategy;

    public static function createStrategy($nameOrClassConfig)
    {
        if (in_array($nameOrClassConfig, array_keys(static::BUILTIN_STRATEGIES))) {
            return app(static::BUILTIN_STRATEGIES[$nameOrClassConfig]);
        }

        if (class_exists($nameOrClassConfig) && $nameOrClassConfig instanceof Strategies\StrategyInterface) {
            return app($nameOrClassConfig);
        }

        throw new InvalidArgumentException($nameOrClassConfig . ' is an unsupported UrlHandler strategy, please see the documentation');
    }

    public function __construct(Strategies\StrategyInterface $strategy)
    {
        $this->strategy = $strategy;
    }

    public function getDataFromRequest(Request $request): Collection
    {
        return $this->strategy->getDataFromRequest($request);
    }

    public function createUrl($disk, $mediaPath, ?array $transformations = [], $version = null): string
    {
        if (is_string($transformations) && $transformations) {
            $transformations = preg_split('/[|,]/', $transformations);

            $transformations = collect($transformations)->mapWithKeys(function ($value) {
                preg_match('/^(?<key>[A-Za-z]+)(?:[:_=](?<value>.*)){0,1}$/', $value, $matches);

                if ($matches) {
                    return [$matches['key'] => ($matches['value'] ?? true)];
                }

                return [$value => true];
            });

            $transformations = $transformations->filter();
        } elseif ($transformations) {
            throw new InvalidArgumentException('Currently createUrl() only supports the string format for specifying transformations');
        } else {
            $transformations = collect();
        }

        return $this->strategy->toUrl('magick.render.' . $disk, $mediaPath, $transformations, $version);
    }
}

