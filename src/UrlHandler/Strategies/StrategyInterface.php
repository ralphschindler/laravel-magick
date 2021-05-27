<?php

namespace LaravelMagick\UrlHandler\Strategies;

use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use LaravelMagick\Eloquent\AbstractMedia;

interface StrategyInterface
{
    public function getDataFromRequest(Request $request): Collection;
    public function toUrl($route, $mediaPath, Collection $transformations = null, string $version = null);
}
