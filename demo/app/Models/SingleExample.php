<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use LaravelMagick\Eloquent\HasMagick;

class SingleExample extends Model
{
    use HasMagick;

    protected $casts = [
        'variations' => 'json'
    ];

    protected $magick = [
        'image' => [
            'type' => 'image',
            'disk' => 'public',
            'path' => 'single-examples/image-{id}.{extension}',
        ],
        'file' => [
            'type' => 'document',
            'disk' => 'public',
            'path' => 'single-examples/document-{id}.{extension}',
            'screenshot' => [
                'path' => 'single-examples/document-{id}-screenshot.jpg'
            ]
        ],
    ];
}
