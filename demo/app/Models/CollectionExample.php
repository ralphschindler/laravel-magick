<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use LaravelMagick\Eloquent\HasMagick;

class CollectionExample extends Model
{
    use HasMagick;

    protected $magick = [
        'images' => [
            'path' => 'image-collection-examples/{id}/{index}.{extension}',
            'collection' => true
        ]
    ];
}
