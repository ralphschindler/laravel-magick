<?php

namespace LaravelMagick\Test\Unit\ImageTransformer;

use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use LaravelMagick\ImageTransformer\ImageTransformer;
use LaravelMagick\Test\Unit\AbstractTestCase;

class ImageTransformerTest extends AbstractTestCase
{
    public function testImageTransformerHasTransformations()
    {
        $config = include __DIR__ . '/../../../config/magick.php';

        $imageTransformer = new ImageTransformer(
            ImageTransformer::createTransformationCollection(Arr::get($config, 'render.transformation.transformers'))
        );

        $this->assertInstanceOf(Collection::class, $imageTransformer->transformations);
        $this->assertCount(7, $imageTransformer->transformations);
    }

    public function testImageTransformerSetsQuality()
    {
        $imageTransformer = new ImageTransformer(collect());

        $bytesOriginal = file_get_contents(__DIR__ . '/TestAssets/picture.jpg');

        $newBytes = $imageTransformer->transform(collect(['quality' => 50]), $bytesOriginal);

        $this->assertLessThan(strlen($bytesOriginal), strlen($newBytes));
    }
}

