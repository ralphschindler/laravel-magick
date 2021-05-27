<?php

namespace LaravelMagick\Test\Unit\Eloquent;

use LaravelMagick\Eloquent\MagickObserver;
use LaravelMagick\Eloquent\AbstractMedia;
use LaravelMagick\Test\Unit\AbstractTestCase;

class MagickObserverTest extends AbstractTestCase
{
    public function testRetrievedSetsStateOnImage()
    {
        $foo = new TestAssets\FooModel();
        $foo->setRawAttributes([
            'id' => 1,
            'image' => '{"path": "foo/bar.jpg", "extension": "jpg", "width": 1, "height": 1, "hash": "1234", "timestamp": 12345, "metadata": []}'
        ], true);

        $observer = new MagickObserver(TestAssets\FooModel::class);
        $observer->retrieved($foo);

        $this->assertInstanceOf(Image::class, $foo->image);
        $this->assertEquals('foo/bar.jpg', $foo->image->toArray()['path']);
    }

    public function testSavingRestoresModelAttributes()
    {
        $foo = new TestAssets\FooModel();
        $foo->image->setStateFromAttributeData([
            'path' => 'foo/bar.jpg',
            'extension' => 'jpg',
            'animated' => false,
            'width' => 1,
            'height' => 1,
            'hash' => '1234',
            'timestamp' => 12345,
            'metadata' => []
        ]);

        $observer = new MagickObserver(TestAssets\FooModel::class);
        $observer->saving($foo);

        $this->assertEquals('{"index":null,"path":"foo\/bar.jpg","extension":"jpg","animated":false,"width":1,"height":1,"hash":"1234","timestamp":12345,"metadata":[]}', $foo->image);
    }

    public function testSavedRestoresImage()
    {
        $foo = new TestAssets\FooModel();
        $foo->setRawAttributes([
            'id' => 1,
            'image' => '{"path": "foo/bar.jpg", "extension": "jpg", "width": 1, "height": 1, "hash": "1234", "timestamp": 12345, "metadata": []}'
        ], true);

        $observer = new MagickObserver(TestAssets\FooModel::class);
        $observer->saved($foo);

        $this->assertInstanceOf(Image::class, $foo->image);
    }
}

