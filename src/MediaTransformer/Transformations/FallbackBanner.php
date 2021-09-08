<?php

namespace LaravelMagick\MediaTransformer\Transformations;

use Illuminate\Support\Collection;
use Imagick;
use ImagickDraw;

class FallbackBanner implements TransformationInterface
{
    public function apply(Collection $arguments, Imagick $imagick)
    {
        if (!isset($arguments['fallbackbanner'])) {
            return;
        }

        [$originalWidth, $originalHeight] = [$imagick->getImageWidth(), $imagick->getImageHeight()];

        foreach ($imagick as $image) {
            $draw = new ImagickDraw();
            $draw->setStrokeWidth(1);
            $draw->line(1, 1, $originalWidth - 1, $originalHeight - 1);
            $draw->line(1, $originalHeight-1, $originalWidth-1, 1);

            $image->drawImage($draw);
        }
    }
}
