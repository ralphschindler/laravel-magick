<?php

namespace LaravelMagick\MediaTransformer\Transformations;

use Illuminate\Support\Collection;
use Imagick;

class JpegExif implements TransformationInterface
{
    protected $strip = true;

    public function apply(Collection $arguments, Imagick $imagick)
    {
        if ($this->strip) {
            $imagick->stripImage();
        }
    }
}

