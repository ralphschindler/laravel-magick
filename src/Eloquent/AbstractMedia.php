<?php

namespace LaravelMagick\Eloquent;

use ArrayAccess;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Filesystem\FilesystemManager;
use Illuminate\Support\Collection;
use Illuminate\Support\Traits\Macroable;
use JsonSerializable;
use OutOfBoundsException;
use RuntimeException;
use LaravelMagick\UrlHandler\UrlHandler;

/**
 * @property-read $disk
 * @property-read $index
 * @property-read $path,
 * @property-read $extension,
 * @property-read $hash,
 * @property-read $timestamp,
 */
abstract class AbstractMedia implements JsonSerializable
{
    use Macroable;

    protected ?int $index = null;
    protected string $path = '';
    protected string $extension = '';

    protected string $hash = '';
    protected int $timestamp = 0;

    protected array $defaultProperties = [];

    public Collection $properties;
    public Collection $metadata;

    protected bool $exists = false;
    protected bool $flush = false;
    protected ?string $data = null;
    protected ?string $removeAtPathOnFlush = null;
    protected bool $isReadOnly = false;

    public function __construct(protected string $disk, protected string $pathTemplate, protected array $presets, protected array $config)
    {
        $this->properties = new Collection;
        $this->metadata = new Collection;
    }

    public function setIndex($index): static
    {
        $this->index = $index;

        return $this;
    }

    public function setReadOnly(): static
    {
        $this->isReadOnly = true;

        return $this;
    }

    public function exists(): bool
    {
        return $this->exists;
    }

    public function url($transformations = null): string
    {
        $renderRouteEnabled = config('magick.render.enable');

        if ($renderRouteEnabled === false && $transformations) {
            throw new RuntimeException('Cannot process render transformation options unless the rendering route is enabled');
        }

        $globalPresets = config('magick.urls.presets');

        $transformations = $this->presets[$transformations]
            ?? $globalPresets[$transformations]
            ?? $transformations;

        return app(UrlHandler::class)->createUrl($this->disk, $this->path, $transformations); // @todo version
    }

    public function setStateFromAttributeData($attributeData): static
    {
        $this->index = $attributeData['index'] ?? null;
        $this->path = $attributeData['path'] ?? null;
        $this->extension = $attributeData['extension'] ?? null;
        $this->hash = $attributeData['hash'] ?? null;
        $this->timestamp = $attributeData['timestamp'] ?? null;
        $this->properties = new Collection($attributeData['properties'] ?? []);
        $this->metadata = new Collection($attributeData['metadata'] ?? []);
        $this->exists = true;

        // type specific property handling
        foreach ($this->defaultProperties as $name => $defaultValue) {
            if (empty($attributeData[$name])) {
                $this->properties[$name] = $defaultValue;

                continue;
            }

            // ensure the type of the attribute matches that of the defaultValue
            $this->properties[$name] = with($attributeData[$name], function ($value) use ($defaultValue) {
                settype($value, gettype($defaultValue));

                return $value;
            });
        }

        return $this;
    }

    public function getStateAsAttributeData(): array
    {
        return [
            'index'      => $this->index,
            'path'       => $this->path,
            'extension'  => $this->extension,
            'hash'       => $this->hash,
            'timestamp'  => $this->timestamp,
            'properties' => $this->properties->toArray(),
            'metadata'   => $this->metadata->toArray()
        ];
    }

    abstract public function setData($data): static;

    public function metadata()
    {
        return $this->metadata;
    }

    public function updatePath(array $replacements, Model $model): void
    {
        $this->path = $this->pathApplyReplacements($this->path, $replacements, $model);
    }

    public function pathHasReplacements()
    {
        return (bool) preg_match('#{(\w+)}#', $this->path);
    }

    public function pathApplyReplacements(string $path, array $replacements, ArrayAccess $additionalReplacements = null): string
    {
        $pathReplacements = [];
        preg_match_all('#{(\w+)}#', $path, $pathReplacements);

        foreach ($pathReplacements[1] as $pathReplacement) {
            if (in_array($pathReplacement, ['index', 'extension', 'hash', 'timestamp'])) {
                $path = str_replace("{{$pathReplacement}}", $this->{$pathReplacement}, $path);
                continue;
            }

            if ($replacements && isset($replacements[$pathReplacement]) && $replacements[$pathReplacement] != '') {
                $path = str_replace("{{$pathReplacement}}", $replacements[$pathReplacement], $path);
                continue;
            }

            if ($additionalReplacements && $additionalReplacements->offsetExists($pathReplacement) && $additionalReplacements->offsetGet($pathReplacement) != '') {
                $path = str_replace("{{$pathReplacement}}", $additionalReplacements->offsetGet($pathReplacement), $path);
            }
        }

        return $path;
    }

    public function isFullyRemoved()
    {
        return ($this->flush === true && $this->removeAtPathOnFlush !== '' && $this->path === '');
    }

    public function remove()
    {
        if ($this->isReadOnly) {
            throw new RuntimeException('Cannot remove an image marked as read only');
        }

        if ($this->path == '') {
            throw new RuntimeException('Called remove on an image that has no path');
        }

        $this->exists = false;
        $this->flush = true;
        $this->removeAtPathOnFlush = $this->path;

        $this->index = null;
        $this->path = '';
        $this->hash = '';
        $this->timestamp = 0;

        $this->properties = new Collection;
        $this->metadata = new Collection;
    }

    public function flush()
    {
        if ($this->isReadOnly) {
            throw new RuntimeException('Cannot flush an image marked as read only');
        }

        if (!$this->flush) {
            return;
        }

        $filesystem = app(FilesystemManager::class)->disk($this->disk);

        if ($this->removeAtPathOnFlush) {
            $filesystem->delete($this->removeAtPathOnFlush);

            if (method_exists($this, 'onFlushDelete')) {
                $this->onFlushDelete();
            }
        }

        if ($this->data) {
            if ($this->pathHasReplacements()) {
                throw new RuntimeException('The image path still has an unresolved replacement in it ("{...}") and cannot be saved: ' . $this->path);
            }

            $filesystem->put($this->path, $this->data);

            if (method_exists($this, 'onFlushPut')) {
                $this->onFlushPut();
            }
        }

        $this->flush = false;
    }

    public function __get($name)
    {
        $properties = [
            'disk'      => $this->disk,
            'index'     => $this->index,
            'path'      => $this->path,
            'extension' => $this->extension,
            'hash'      => $this->hash,
            'timestamp' => $this->timestamp,
        ];

        if (array_key_exists($name, $properties)) {
            return $properties[$name];
        }

        return $this->properties[$name] ?? throw new OutOfBoundsException("$name is not a valid property");
    }

    public function __isset(string $name): bool
    {
        $properties = [
            'disk'      => $this->disk,
            'index'     => $this->index,
            'path'      => $this->path,
            'extension' => $this->extension,
            'hash'      => $this->hash,
            'timestamp' => $this->timestamp,
        ];

        if (array_key_exists($name, $properties)) {
            return isset($properties[$name]);
        }

        if (!array_key_exists($name, $properties)) {
            throw new OutOfBoundsException("Property $name is not accessible");
        }

        return $this->properties[$name] ?? throw new OutOfBoundsException("$name is not a valid property");
    }

    public function toArray(): array
    {
        return $this->getStateAsAttributeData();
    }

    public function jsonSerialize(): ?array
    {
        if ($this->exists) {
            return [
                'path'     => $this->path,
                'metadata' => $this->metadata
            ];
        }

        return null;
    }

    public function __clone()
    {
        $this->metadata = clone $this->metadata;
    }
}
