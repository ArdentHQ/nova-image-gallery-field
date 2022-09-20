<?php

declare(strict_types=1);

namespace Ardenthq\ImageGalleryField;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Nova\Fields\Trix;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Trix\DeleteAttachments;
use Laravel\Nova\Trix\DetachAttachment;
use Laravel\Nova\Trix\DiscardPendingAttachments;
use Laravel\Nova\Trix\PendingAttachment;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class ImageGalleryField extends Trix
{
    /**
     * The field's component.
     *
     * @var string
     */
    public $component = 'image-gallery-field';

    public $showOnIndex = false;

    public array $rulesMessages = [];

    /**
     * Create a new field.
     *
     * @param  string  $name
     * @param  string|\Closure|callable|object|null  $attribute
     * @param  (callable(mixed, mixed, ?string):mixed)|null  $resolveCallback
     * @return void
     */
    public function __construct($name, $attribute = null, callable $resolveCallback = null)
    {
        parent::__construct($name, $attribute, $resolveCallback);

        $this->withFiles = true;

        $this->disk('public')->path('/');

        $this->attach(new StorePendingImage($this))
             ->detach(new DetachAttachment())
             ->delete(new DeleteAttachments($this))
             ->discard(new DiscardPendingAttachments())
             ->prunable();
    }

    /**
     * Hydrate the given attribute on the model based on the incoming request.
     *
     * @param  string  $requestAttribute
     * @param  Model  $model
     * @param  string  $attribute
     * @return void
     */
    protected function fillAttribute(NovaRequest $request, $requestAttribute, $model, $attribute)
    {
        $model::saved(function ($model) use ($request, $attribute) {
            /** @var UploadedFile[] $newImages */
            $newImages      = $request->get($attribute);
            /** @var (mixed)[] $imagesToDelete */
            $imagesToDelete = $request->get($attribute.'_delete', []);
            /** @var (mixed)[] $imageOrder */
            $imageOrder     = $request->get($attribute.'_order', []);

            collect($newImages)->each(function ($tempImageId) use ($model, $attribute, &$imageOrder) {
                $imageOrderIndex = array_search('new:'.$tempImageId, $imageOrder, true);

                $pendingAttachment = PendingAttachment::find($tempImageId);

                $stream = Storage::disk($this->disk)->readStream($this->storagePath.$pendingAttachment->attachment);

                $media = $model->addMediaFromStream($stream)->toMediaCollection($attribute);

                $imageOrder[$imageOrderIndex] = $media->id;
            });

            collect($imagesToDelete)->each(static function (int $mediaId) use ($model) {
                $model->media()->find($mediaId)->delete();
            });

            if ($imageOrder === []) {
                return;
            }

            Media::setNewOrder($imageOrder);
        });
    }

   /**
    * Resolve the given attribute from the given resource.
    *
    * @param  mixed  $resource
    * @param  string  $attribute
    * @return mixed
    */
   protected function resolveAttribute($resource, $attribute)
   {
       return $resource->getMedia($attribute)->map(static function ($media, $index) {
           return [
               'id'    => $media->id,
               'url'   => $media->getUrl(),
               'order' => $index,
           ];
       });
   }

    /**
     * Set custom error messages for the validation rules.
     *
     * @param  array<string, string>  $messages
     * @return $this
     */
    public function rulesMessages(array $messages) : self
    {
        $this->rulesMessages = $messages;

        return $this;
    }
}
