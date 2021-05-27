<?php
namespace LaravelMagick\View;

class BladeDirectives
{
    public static function placeholderImageUrl($args)
    {
        $placeholderFilename = config('magick.render.placeholder.filename');
        $path = "{$placeholderFilename}.{$args}.png";
        return route('magick.render', $path);
    }
}
