<?php

namespace LaravelMagick\Eloquent;

use DomainException;
use RuntimeException;

/**
 * @mixin \Illuminate\Database\Eloquent\Model
 */
trait HasMagick
{
    /** @var AbstractMedia[]|MediaCollection[] */
    protected static $magickMediaPrototypes = [];

    /** @var AbstractMedia[]|MediaCollection[] */
    protected array $magickMedia = [];

    public static function bootHasMagick()
    {
        $observer = new MagickObserver(get_called_class());

        // register directly so that the instance is preserved (not preserved via static::observe())
        static::registerModelEvent('retrieved', [$observer, 'retrieved']);
        static::registerModelEvent('saving', [$observer, 'saving']);
        static::registerModelEvent('saved', [$observer, 'saved']);
        static::registerModelEvent('deleted', [$observer, 'deleted']);
    }

    public function initializeHasMagick()
    {
        if (!empty($this->magickMedia)) {
            throw new RuntimeException('$magickMedia should be empty, are you sure you have your configuration in the right place?');
        }

        if (empty($this->magick) || !property_exists($this, 'magick')) {
            throw new RuntimeException('You are using ' . __TRAIT__ . ' but have not yet configured it through $magick, please see the docs');
        }

        foreach ($this->magick as $attribute => $config) {
            if (is_string($config)) {
                $config = ['path' => $config];
            }

            if (!is_array($config) || !isset($config['path'])) {
                throw new RuntimeException('configuration must be a string (path) or array with a path key');
            }

            $pathTemplate = $config['path'];

            if (!isset(static::$magickMediaPrototypes[$attribute])) {
                $disk = $config['disk'] ?? config('magick.filesystem.default');

                if (config('magick.filesystem.disk_render_prefixes') === null) {
                    throw new DomainException("$disk was the configured disk, but a prefix was not found in magick.filesystem.disk_render_prefixes");
                }

                $type = $config['type'] ?? 'image';

                $typeClass = match (strtolower($type)) {
                    'document' => Document::class,
                    'image' => Image::class,
                    // @todo Video, Audio
                };

                $presets = $config['presets'] ?? [];

                unset($config['type'], $config['disk'], $config['path'], $config['presets']);

                $prototype = app($typeClass, compact('disk', 'pathTemplate', 'presets', 'config'));

                if (isset($config['collection']) && $config['collection'] === true) {
                    $prototype = app(MediaCollection::class, ['prototype' => $prototype]);
                }

                static::$magickMediaPrototypes[$attribute] = $prototype;
            } else {
                $prototype = static::$magickMediaPrototypes[$attribute];
            }

            $this->attributes[$attribute] = $this->magickMedia[$attribute] = clone $prototype;
        }
    }
}
