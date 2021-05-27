<?php

namespace LaravelMagick\Nova;

use Illuminate\Support\Collection;
use Laravel\Nova\Fields\Field;
use Laravel\Nova\Http\Requests\NovaRequest;
use LaravelMagick\Eloquent\AbstractMedia;
use LaravelMagick\Eloquent\Document;
use LaravelMagick\Eloquent\MediaCollection;

class MagickField extends Field
{
    public $component = 'magick';

    public $showOnIndex = false;

    protected $thumbnailUrlModifiers;
    protected $previewUrlModifiers;

    protected function fillAttribute(NovaRequest $request, $requestAttribute, $model, $attribute)
    {
        if (!$request->exists($requestAttribute)) {
            return;
        }

        $value = json_decode($request[$requestAttribute], true);

        /** @var AbstractMedia|MediaCollection $fieldAttribute */
        $fieldAttribute = $model->{$attribute};

        if (!$fieldAttribute instanceof AbstractMedia && !$fieldAttribute instanceof MediaCollection) {
            throw new \RuntimeException('Field must be an MagickField field');
        }

        ($fieldAttribute instanceof MediaCollection)
            ? $this->resolveMediaCollectionFromFormData($value, $fieldAttribute)
            : $this->resolveMediaFromFormData($value, $fieldAttribute);

        $fieldAttribute->updatePath([], $model);
    }


    public function jsonSerialize()
    {
        if ($this->value instanceof MediaCollection) {
            $isCollection = true;

            $value = [
                'autoincrement' => $this->value->getAutoincrement(),
                'images'        => []
            ];

            foreach ($this->value as $image) {
                $value['images'][] = $this->jsonSerializeImage($image);
            }
        } else {
            $isCollection = false;

            $value = ($this->value->exists()) ? $this->jsonSerializeImage($this->value) : null;
        }

        return array_merge(parent::jsonSerialize(), [
            'value'        => $value,
            'isCollection' => $isCollection
        ]);
    }

    protected function jsonSerializeImage(AbstractMedia $media)
    {
        return [
            'previewUrl' => ($media instanceof Document && $media->hasScreenshot()) ? $media->screenshotUrl() : $media->url(),
            'thumbnailUrl' => ($media instanceof Document && $media->hasScreenshot()) ? $media->screenshotUrl() : $media->url(),
            'path'       => $media->path,
            'metadata'   => $media->metadata
        ];
    }

    // /**
    //  * @param $previewUrlModifiers
    //  * @return $this
    //  */
    // public function previewUrlModifiers($previewUrlModifiers)
    // {
    //     $this->previewUrlModifiers = $previewUrlModifiers;
    //
    //     return $this;
    // }
    //
    // /**
    //  * @param $thumbnailUrlModifiers
    //  * @return $this
    //  */
    // public function thumbnailUrlModifiers($thumbnailUrlModifiers)
    // {
    //     $this->thumbnailUrlModifiers = $thumbnailUrlModifiers;
    //
    //     return $this;
    // }


    protected function resolveMediaFromFormData($formData, AbstractMedia $image)
    {
        if ($formData === null) {

            if ($image->exists()) {
                $image->remove();
            }

            return;
        }

        if ($formData['fileData']) {
            $image->setData($formData['fileData']);
        }

        $image->metadata = new Collection($formData['metadata']);
    }

    protected function resolveMediaCollectionFromFormData(array $formData, MediaCollection $mediaCollection)
    {
        // // create a collection of mapped path=>image of the existing images
        // $existingImages = $imageCollection->mapWithKeys(function ($image, $index) {
        //     return [$image->path => ['image' => $image, 'original_index' => $index]];
        // });
        //
        // $newCollectionForImages = new Collection;
        //
        // // iterate over provided value from form, start creating an array of images for the new ImageCollection
        // foreach ($formData as $imageIndex => $imageData) {
        //     if ($imageData['path']) {
        //         $image = $existingImages[$imageData['path']]['image'];
        //         unset($existingImages[$imageData['path']]);
        //     } else {
        //         $image = $imageCollection->createImage($imageData['fileData']);
        //     }
        //
        //     // if bytes were provided, set them
        //     if (isset($imageData['fileData'])) {
        //         $image->setData($imageData['fileData']);
        //     }
        //
        //     // store the metadata
        //     $image->metadata = new Collection($imageData['metadata']);
        //
        //     $newCollectionForImages[$imageIndex] = $image;
        // }
        //
        // // what is left over needs to be removed from the original attribute
        // foreach ($existingImages as $leftOverImages) {
        //     unset($imageCollection[$leftOverImages['original_index']]);
        // }
        //
        // // finally replace the image collection's interal/wrapped collection
        // $imageCollection->replaceWrappedCollectionForImages($newCollectionForImages);
    }
}

