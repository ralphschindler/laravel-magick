<?php

namespace LaravelMagick\Test\Unit\ImageTransformer;

use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use LaravelMagick\MediaTransformer\MediaTransformer;
use LaravelMagick\Test\Unit\AbstractTestCase;

class ImageTransformerTest extends AbstractTestCase
{
    public function testImageTransformerHasTransformations()
    {
        $config = include __DIR__ . '/../../../config/magick.php';

        $imageTransformer = new MediaTransformer(
            MediaTransformer::createTransformationCollection(Arr::get($config, 'render.transformation.transformers'))
        );

        $this->assertInstanceOf(Collection::class, $imageTransformer->transformations);
        $this->assertCount(7, $imageTransformer->transformations);
    }

    public function testImageTransformerSetsQuality()
    {
        $imageTransformer = new MediaTransformer(collect());

        $bytesOriginal = file_get_contents(__DIR__ . '/TestAssets/picture.jpg');

        $newBytes = $imageTransformer->transform(collect(['quality' => 50]), $bytesOriginal);

        $this->assertLessThan(strlen($bytesOriginal), strlen($newBytes));
    }
}

