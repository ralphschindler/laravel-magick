<?php

namespace LaravelMagick\Eloquent;

use Carbon\Carbon;
use finfo;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Filesystem\FilesystemManager;
use Illuminate\Http\UploadedFile;
use Imagick;
use LaravelMagick\UrlHandler\UrlHandler;
use RuntimeException;

class Document extends AbstractMedia
{
    public function setData($data): static
    {
        if ($this->isReadOnly) {
            throw new RuntimeException('Cannot call setData on an image marked as read only');
        }

        if ($this->path && $this->filesystem->exists($this->path)) {
            $this->removeAtPathOnFlush = $this->path;
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

        switch ($mimeType) {
            case 'application/pdf':
                $this->extension = 'pdf';
                break;
            default:
                throw new RuntimeException('Unsupported mime-type for expected image: ' . $mimeType);
        }

        return $this;
    }

    public function updatePath(array $replacements, Model $model): void
    {
        parent::updatePath($replacements, $model);

        if ($this->config['screenshot']['path']) {
            $this->properties['screenshot'] = $this->pathApplyReplacements(
                $this->config['screenshot']['path'],
                $replacements,
                $model
            );
        }
    }

    public function onFlushDelete()
    {
        if (isset($this->properties['screenshot'])) {
            $filesystem = app(FilesystemManager::class)->disk($this->disk);

            $filesystem->delete($this->properties['screenshot']);
        }
    }

    public function onFlushPut()
    {
        if (isset($this->config['screenshot'])) {
            $im = new Imagick;
            $im->readImageBlob($this->data);
            $im->setIteratorIndex(0);
            $im->setImageColorspace(255);
            $im->setResolution(300, 300);
            $im->setCompressionQuality(95);
            $im->setImageFormat('jpeg');

            $filesystem = app(FilesystemManager::class)->disk($this->disk);

            $filesystem->put($this->properties['screenshot'], $im->getImageBlob());

            $im->clear();
            $im->destroy();
        }
    }

    public function hasScreenshot(): bool
    {
        return isset($this->properties['screenshot']);
    }

    public function screenshotUrl(): string
    {
        if (!isset($this->properties['screenshot'])) {
            throw new RuntimeException('This document does not have a screenshot, was it configured to have one?');
        }

        return app(UrlHandler::class)->createUrl($this->disk, $this->properties['screenshot']);
    }

}
