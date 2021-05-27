<?php

namespace LaravelMagick\Test\Unit\Eloquent\TestAssets;

use Illuminate\Database\Eloquent\Model;
use LaravelMagick\Eloquent\HasMagick;
use LaravelMagick\Eloquent\AbstractMedia;

/**
 * @property AbstractMedia $image
 */
class FooModel extends Model
{
    use HasMagick;

    protected array $magick = [
        'image' => 'images/{id}.{extension}'
    ];
}
