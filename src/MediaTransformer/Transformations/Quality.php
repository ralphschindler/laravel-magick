<?php

namespace LaravelMagick\MediaTransformer\Transformations;

use Illuminate\Support\Collection;
use Imagick;

class Quality implements TransformationInterface
{
    protected $automatic = false;

    protected $defaultQuality = .75;

    public function __construct($defaultQuality = .75, $automatic = false)
    {
        $this->defaultQuality = $defaultQuality;
        $this->automatic = $automatic;
    }

    public function apply(Collection $arguments, Imagick $imagick)
    {
        // if has command || automatic
        if (!$arguments->has('quality') && !$this->automatic) {
            return;
        }

        $quality = $arguments->get('quality') ?? $this->defaultQuality;

        $imagick->setCompressionQuality($quality);
        $imagick->setImageCompressionQuality($quality);
    }
}
