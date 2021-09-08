<?php

namespace LaravelMagick\Controllers;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Filesystem\FilesystemManager;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use LaravelMagick\MediaTransformer\MediaTransformer;
use LaravelMagick\UrlHandler\UrlHandler;

class MagickController extends Controller
{
    public function render(Request $request)
    {
        $path = $request->route('path');
        $disk = $request->route('disk');

        $cacheEnabled = config('magick.render.caching.enable', false);
        $cacheDriver = config('magick.render.caching.driver', 'disk');

        if ($cacheEnabled && Cache::has($path)) {
            return Cache::store($cacheDriver)->get($path);
        }

        // Path traversal detection: 404 the user, no need to give additional information
        abort_if((in_array($path[0], ['.', '/']) || strpos($path, '../') !== false), 404);

        // $disk = config('magick.filesystem', config('filesystems.default'));

        /** @var Filesystem $filesystem */
        $filesystem = app(FilesystemManager::class)->disk($disk);

        $mediaRequestData = app(UrlHandler::class)->getDataFromRequest($request);

        $mediaActualPath = $mediaRequestData->get('path');

        // assume the mime type is PNG unless otherwise specified
        $mimeType = 'image/png';
        $mediaBytes = null;

        // step 1: if placeholder request, generate a placeholder
        // if ($filenameWithoutExtension === config('magick.render.placeholder.filename') && config('magick.render.placeholder.enable')) {
        if (
            config('magick.render.placeholder.enable')
            && $mediaActualPath === config('magick.render.placeholder.filename')
        ) {
            list ($placeholderWidth, $placeholderHeight) = isset($modifierOperators['size']) ? explode('x', $modifierOperators['size']) : [400, 400];
            $mediaBytes = $this->createPlaceHolderImage($mediaRequestData);
        }

        // step 2: no placeholder, look for actual file on designated filesystem
        if (!$mediaBytes) {
            try {
                $mediaBytes = $filesystem->get($mediaActualPath);
                $mimeType = $filesystem->getMimeType($mediaActualPath);
            } catch (FileNotFoundException $e) {
                $mediaBytes = null;
            }
        }

        // step 3: no placeholder, no primary FS image, look for fallback image on alternative filesystem if enabled
        if (!$mediaBytes && config('magick.render.fallback.enable')) {
            /** @var Filesystem $fallbackFilesystem */
            $fallbackFilesystem = app(FilesystemManager::class)->disk(config('magick.render.fallback.filesystem'));

            try {
                $mediaBytes = $fallbackFilesystem->get($mediaActualPath);
                $mimeType = $fallbackFilesystem->getMimeType($mediaActualPath);

                if (config('magick.render.fallback.mark_images')) {
                    $mediaRequestData['fallbackbanner'] = true;
                }
            } catch (FileNotFoundException $e) {
                $mediaBytes = null;
            }
        }

        // step 4: no placeholder, no primary FS image, no fallback, generate a placeholder if enabled for missing files
        if (!$mediaBytes && config('magick.render.placeholder.use_for_missing_files') === true) {
            list ($placeholderWidth, $placeholderHeight) = isset($modifierOperators['size']) ? explode('x', $modifierOperators['size']) : [400, 400];
            $mediaBytes = $this->createPlaceHolderImage($mediaRequestData);
        }

        abort_if(!$mediaBytes, 404); // no image, no fallback, no placeholder

        $mediaBytes = app(MediaTransformer::class)->transform($mediaRequestData, $mediaBytes);

        $browserCacheMaxAge = config('magick.render.browser_cache_max_age');

        $response = response()
            ->make($mediaBytes)
            ->header('Content-type', $mimeType)
            ->header('Cache-control', "public, max-age=$browserCacheMaxAge");

        if ($cacheEnabled) {
            Cache::store($cacheDriver)->put($path, $response, config('magick.render.caching.ttl', 60));
        }

        return $response;
    }

    protected function createPlaceHolderImage(Collection $imageRequestData)
    {
        // $image = (new ImageManager(['driver' => 'imagick']))->canvas($width, $height, $backgroundColor);
        //
        // $image->text("{$width}x{$height}", $width / 2, $height / 2, function(AbstractFont $font) use ($width) {
        //     $font->align('center');
        //     $font->valign('middle');
        //     $font->color('000000');
        //     $font->file(__DIR__ . '/../../fonts/OverpassMono-Regular.ttf');
        //     $font->size(ceil((.9 * $width) / 7));
        // });
        //
        // return $image->encode('png')->__toString();
    }
}
