<?php

declare(strict_types=1);

namespace Ardenthq\ImageGalleryField;

use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Laravel\Nova\Fields\Attachments\PendingAttachment;

class StorePendingImage
{
    use ValidatesRequests;

    public function __construct(
        /**
         * The field instance.
         */
        public ImageGalleryField $field
    ) {
    }

    /**
     * Attach a pending attachment to the field.
     *
     * @param Request $request
     * @return string
     */
    public function __invoke(Request $request)
    {
        $this->validate(
            $request,
            [
                'attachment' => [...$this->field->imageRules, 'required'],
                'draftId'    => 'required',
            ],
            $this->field->imageRulesMessages
        );

        /** @var UploadedFile $file */
        $file = $request->file('attachment');
        /** @var string $originalFileName */
        $filePathinfo = pathinfo($file->getClientOriginalName());
        /** @var string $storageDir */
        $storageDir = rtrim($this->field->getStorageDir(), '/') . '/pending-images';
        /** @var string $disk */
        $disk = $this->field->getStorageDisk();
        /** @var string $draftId */
        $draftId = (string) $request->input('draftId');

        $attachment = $file->store($storageDir, $disk);

        $attachment = PendingAttachment::create([
            'draft_id'      => $draftId,
            'attachment'    => $attachment,
            'disk'          => $disk,
            'original_name' => Str::slug($filePathinfo['filename']) . "." . strtolower($filePathinfo['extension'])
        ]);

        /** @var FilesystemAdapter $storage */
        $storage = Storage::disk($disk);
        $url     = $storage->url($attachment->attachment);

        // We need to return a string to make it compatible with the parent class
        /** @var string $result */
        $result = json_encode([
            'url' => $url,
            'id'  => $attachment->id,
        ]);

        return $result;
    }
}
