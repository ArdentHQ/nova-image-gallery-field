<?php

declare(strict_types=1);

namespace Ardenthq\ImageGalleryField;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Laravel\Nova\Fields\Attachments\DeleteAttachments;
use Laravel\Nova\Fields\Attachments\DetachAttachment;
use Laravel\Nova\Fields\Attachments\DiscardPendingAttachments;
use Laravel\Nova\Fields\Attachments\PendingAttachment;
use Laravel\Nova\Fields\Trix;
use Laravel\Nova\Http\Requests\NovaRequest;
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

    public array $imageRulesMessages = [];

    /**
     * The validation rules for creation and updates.
     *
     * @var array<int, (string | \Illuminate\Validation\Rule | Rule | callable)>|mixed
     */
    public $imageRules = [];

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
     * Set custom error messages for the validation rules.
     *
     * @param  array<string, string>  $messages
     * @return $this
     */
    public function rulesMessages(array $messages) : self
    {
        $this->imageRulesMessages = $messages;

        return $this;
    }

    /**
     * Set the validation rules for the field.
     *
     * @param callable|array<int, (string | \Illuminate\Validation\Rule | Rule | callable)>|string ...$rules
     * @return $this
     */
    public function rules($rules)
    {
        $this->imageRules = ($rules instanceof Rule || is_string($rules)) ? func_get_args() : $rules;

        return $this;
    }

    /**
     * Hydrate the given attribute on the model based on the incoming request.
     *
     * @param  Model  $model
     * @param  string  $attribute
     * @param  string|null  $requestAttribute
     * @return mixed
     */
    public function fillInto(NovaRequest $request, $model, $attribute, $requestAttribute = null)
    {
        $model::saved(function ($model) use ($request, $attribute) {
            /** @var string[] $newImages */
            $newImages      = $request->get($attribute);
            /** @var (mixed)[] $imagesToDelete */
            $imagesToDelete = $request->get($attribute.'_delete', []);
            /** @var (mixed)[] $imageOrder */
            $imageOrder     = $request->get($attribute.'_order', []);

            collect($newImages)->each(function ($tempImageId) use ($model, $attribute, &$imageOrder) {
                $imageOrderIndex = array_search('new:'.$tempImageId, $imageOrder, true);

                $pendingAttachment = PendingAttachment::find($tempImageId);

                $stream = Storage::disk($this->disk)->readStream($this->storagePath.$pendingAttachment->attachment);

                $media = $model
                    ->addMediaFromStream($stream)
                    ->usingFileName($pendingAttachment->original_name)
                    ->toMediaCollection($attribute);


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
     * Specify that the element should be visible on the index view.
     *
     * @param  (callable():bool)|bool  $callback
     * @return $this
     */
    public function showOnIndex($callback = true)
    {
        $this->showOnIndex = $callback;

        return $this;
    }

   /**
    * Resolve the given attribute from the given resource.
    *
    * @param  mixed  $resource
    * @param  string  $attribute
    * @return mixed
    */
   protected function resolveAttribute($resource, string $attribute): mixed
   {
       return $resource->getMedia($attribute)->map(static function ($media, $index) {
           return [
               'id'    => $media->id,
               'url'   => $media->getUrl(),
               'order' => $index,
           ];
       });
   }
}
