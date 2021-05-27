<?php

namespace LaravelMagick\Eloquent;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use ReflectionProperty;
use RuntimeException;

class MagickObserver
{
    protected ReflectionProperty $magickMediaReflector;

    protected ReflectionProperty $attributesReflector;

    public function __construct($modelClassToObserve)
    {
        $this->magickMediaReflector = new ReflectionProperty($modelClassToObserve, 'magickMedia');
        $this->magickMediaReflector->setAccessible(true);

        $this->attributesReflector = new ReflectionProperty($modelClassToObserve, 'attributes');
        $this->attributesReflector->setAccessible(true);
    }

    /**
     * @param \Illuminate\Database\Eloquent\Model|\LaravelMagick\Eloquent\HasMagick $model
     */
    public function retrieved(Model $model)
    {
        /** @var AbstractMedia[]|MediaCollection[] $magickMedia */
        $magickMedia = $this->magickMediaReflector->getValue($model);

        $modelAttributes = $this->attributesReflector->getValue($model);

        foreach ($magickMedia as $attribute => $image) {
            // in the case a model was retrieved and the image column was not returned
            if (!array_key_exists($attribute, $modelAttributes)) {
                continue;
            }

            $attributeData = $modelAttributes[$attribute];
            $modelAttributes[$attribute] = $image;

            if ($attributeData == '') {
                continue;
            }

            if (is_string($attributeData)) {
                $attributeData = json_decode($attributeData, true);
            }

            $image->setStateFromAttributeData($attributeData);
        }

        $this->attributesReflector->setValue($model, $modelAttributes);
    }

    /**
     * @param \Illuminate\Database\Eloquent\Model|\LaravelMagick\Eloquent\HasMagick $model
     */
    public function saving(Model $model)
    {
        /** @var AbstractMedia[]|MediaCollection[] $magickMedia */
        $magickMedia = $this->magickMediaReflector->getValue($model);

        $casts = $model->getCasts();

        $modelAttributes = $this->attributesReflector->getValue($model);

        foreach ($magickMedia as $attribute => $image) {
            if ($image->pathHasReplacements()) {
                $image->updatePath([], $model);
            }

            if ($image instanceof MediaCollection) {
                $image->purgeRemovedImages();
            } elseif ($image instanceof AbstractMedia && !$image->exists()) {
                $modelAttributes[$attribute] = null;
                continue;
            }

            $attributeData = $image->getStateAsAttributeData();

            $value = (isset($casts[$attribute]) && $casts[$attribute] === 'json')
                ? $attributeData
                : json_encode($attributeData);

            $modelAttributes[$attribute] = $value;
        }

        $this->attributesReflector->setValue($model, $modelAttributes);
    }

    /**
     * @param \Illuminate\Database\Eloquent\Model|\LaravelMagick\Eloquent\HasMagick $model
     */
    public function saved(Model $model)
    {
        /** @var AbstractMedia[]|MediaCollection[] $magickMedia */
        $magickMedia = $this->magickMediaReflector->getValue($model);

        $casts = $model->getCasts();

        $errors = [];

        $modelAttributes = $this->attributesReflector->getValue($model);

        foreach ($magickMedia as $attribute => $image) {
            if ($image->pathHasReplacements()) {

                $image->updatePath([], $model);

                if ($image->pathHasReplacements()) {
                    $errors[] = "After saving row, image for attribute {$attribute}'s path still contains unresolvable path replacements";
                }

                $imageState = $image->getStateAsAttributeData();

                $value = (isset($casts[$attribute]) && $casts[$attribute] === 'json')
                    ? $imageState
                    : json_encode($imageState);

                $model->getConnection()
                    ->table($model->getTable())
                    ->where($model->getKeyName(), $model->getKey())
                    ->update([$attribute => $value]);
            }

            $image->flush();

            $modelAttributes[$attribute] = $image;
        }

        $this->attributesReflector->setValue($model, $modelAttributes);

        if ($errors) {
            throw new RuntimeException(implode('; ', $errors));
        }
    }

    /**
     * @param \Illuminate\Database\Eloquent\Model|\LaravelMagick\Eloquent\HasMagick $model
     */
    public function deleted(Model $model)
    {
        if (in_array(SoftDeletes::class, class_uses_recursive($model)) && !$model->isForceDeleting()) {
            return;
        }

        /** @var AbstractMedia[]|MediaCollection[] $magickMedia */
        $magickMedia = $this->magickMediaReflector->getValue($model);

        foreach ($magickMedia as $image) {
            if ($image->exists()) {
                $image->remove();
                $image->flush();
            }
        }
    }
}
