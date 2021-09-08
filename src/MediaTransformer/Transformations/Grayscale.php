<?php

namespace LaravelMagick\MediaTransformer\Transformations;

use Illuminate\Support\Collection;
use Imagick;

class Grayscale implements TransformationInterface
{
    public function apply(Collection $arguments, Imagick $imagick)
    {
        if (!$arguments->has('grayscale')) {
            return;
        }

        foreach ($imagick as $image) {
            $image->setImageColorspace(Imagick::COLORSPACE_GRAY);
        }
    }
}

