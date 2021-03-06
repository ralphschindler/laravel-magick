<?php

namespace LaravelMagick\UrlHandler\Strategies;

use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use InvalidArgumentException;
use LaravelMagick\Eloquent\AbstractMedia;

class LegacyStrategy implements StrategyInterface
{
    protected $urlModifierRegexes = [
        'width'      => '/^size_(?P<value>\d*){0,1}x(?:\d*){0,1}$/', // set width
        'height'     => '/^size_(?:\d*){0,1}x(?P<value>\d*){0,1}$/', // set height
        'fit'        => '/^fit_(?P<value>[a-z]+)$/', // set height
        'grayscale'  => '/^grayscale$/', // grayscale
        'quality'    => '/^quality_(?P<value>[0-9]+)/', //quality, if applicable
        'background' => '/^bg_(?P<value>[\da-f]{6})$/', // background hex
        'trim'       => '/^trim_(?P<value>\d+)$/', // trim, tolerance
        'crop'       => '/^crop_(?P<value>[\dx]+)$/', // crop operations
        'fill'       => '/^fill$/', // fill operation
        'gravity'    => '/^gravity_(?P<value>[\w_]+)$/', // optional gravity param, g_auto - means center, g_north or g_south
        'static'     => '/^static(?:_(?P<value>\d*)){0,1}$/' // ensure even animated gifs are single frame
    ];

    public function getDataFromRequest(Request $request): Collection
    {
        $path = $request->route('path');

        $imageRequestData = new Collection();

        $pathInfo = pathinfo($path);
        $imagePath = $pathInfo['dirname'] !== '.'
            ? $pathInfo['dirname'] . '/'
            : '';

        $filenameWithoutExtension = $pathInfo['filename'];

        if (strpos($filenameWithoutExtension, '.') !== false) {
            $filenameParts = explode('.', $filenameWithoutExtension);
            $filenameWithoutExtension = $filenameParts[0];
            $imagePath .= "{$filenameWithoutExtension}.{$pathInfo['extension']}";

            $modifierSpecs = array_slice($filenameParts, 1);

            foreach ($modifierSpecs as $modifierSpec) {
                $matches = [];
                foreach ($this->urlModifierRegexes as $modifier => $regex) {
                    if (preg_match($regex, $modifierSpec, $matches)) {
                        $imageRequestData[$modifier] = $matches['value'] ?? true;
                    }
                }
            }
        } else {
            $imagePath .= $pathInfo['basename'];
        }

        $imageRequestData['path'] = $imagePath;

        if (isset($imageRequestData['fit']) && $imageRequestData['fit'] === 'lim') {
            $imageRequestData['fit'] = 'limit';
        }

        return $imageRequestData;
    }

    public function toUrl($route, $mediaPath, Collection $transformations = null, string $version = null): string
    {
        // handle size, width, height
        if ($transformations->has('size')) {
            unset($transformations['width'], $transformations['height']);
        } elseif ($transformations->has('width') || $transformations->has('height')) {
            $transformations['size'] =
                ($transformations['width'] ?? '')
                . 'x'
                . ($transformations['height'] ?? '');

            unset($transformations['width'], $transformations['height']);
        }

        // handle versioning
        if ($version = $transformations->search(function ($value, $key) {
            return preg_match('/^v\d/', $key);
        })) {
            unset($transformations[$version]);
        }

        $transformations = $transformations->map(function ($value, $key) {
            if ($key === 'version') {
                return $value;
            }

            if ($value === true) {
                return $key;
            }

            return $key . '_' . $value;
        })->sort()->implode('.');

        // $path = '';
        //
        // if ($transformations) {
        //     $path .= "$transformations/";
        // }
        //
        // if ($version) {
        //     $path .= "$version/";
        // }
        //
        // $path .= $mediaPath;

        return url()->route($route, [
            'path' => implode('/', array_filter([$transformations, $version, $mediaPath]))
        ]);
    }
}

