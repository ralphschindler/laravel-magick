<?php

namespace LaravelMagick\Eloquent;

use Carbon\Carbon;
use finfo;
use Illuminate\Filesystem\FilesystemManager;
use Illuminate\Http\UploadedFile;
use RuntimeException;

class Image extends AbstractMedia
{
    protected array $defaultProperties = [
        'animated' => false,
        'width' => null,
        'height' => null
    ];

    protected bool $animated = false;
    protected ?int $width = null;
    protected ?int $height = null;

    public function setData($data): static
    {
        if ($this->isReadOnly) {
            throw new RuntimeException('Cannot call setData on an image marked as read only');
        }

        if ($this->path) {
            $filesystem = app(FilesystemManager::class)->disk($this->disk);

            if ($filesystem->exists($this->path)) {
                $this->removeAtPathOnFlush = $this->path;
            }
        }

        static $fInfo = null;

        if (!$fInfo) {
            $fInfo = new finfo;
        }

        if ($data instanceof UploadedFile) {
            $data = file_get_contents($data->getRealPath());
        }

        if (str_starts_with($data, 'data:')) {
            $data = file_get_contents($data);
        }

        list ($width, $height) = getimagesizefromstring($data);

        $mimeType = $fInfo->buffer($data, FILEINFO_MIME_TYPE);

        if (!$mimeType) {
            throw new RuntimeException('Mime type could not be discovered');
        }

        $this->path = $this->pathTemplate;
        $this->exists = true;
        $this->flush = true;
        $this->data = $data;
        $this->timestamp = Carbon::now()->unix();
        $this->hash = md5($data);

        $this->properties['animated'] = false;
        $this->properties['width'] = $width;
        $this->properties['height'] = $height;

        switch ($mimeType) {
            case 'image/jpeg':
                $this->extension = 'jpg';
                break;
            case 'image/png':
                $this->extension = 'png';
                break;
            case 'image/gif':
                $this->extension = 'gif';

                // magic bytes
                $this->properties['animated'] = (bool) preg_match('#(\x00\x21\xF9\x04.{4}\x00\x2C.*){2,}#s', $data);
                break;
            case 'image/webp':
                $this->extension = 'webp';
                break;
            case 'image/bmp':
            case 'image/x-ms-bmp':
                $this->extension = 'bmp';
                break;
            default:
                throw new RuntimeException('Unsupported mime-type for expected image: ' . $mimeType);
        }

        return $this;
    }
}
