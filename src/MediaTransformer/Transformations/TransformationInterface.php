<?php

namespace LaravelMagick\MediaTransformer\Transformations;

use Illuminate\Support\Collection;
use Imagick;

interface TransformationInterface
{
    public function apply(Collection $arguments, Imagick $imagick);
}
