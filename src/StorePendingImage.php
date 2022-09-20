<?php

declare(strict_types=1);

namespace Ardenthq\ImageGalleryField;

use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Nova\Trix\PendingAttachment;
use Laravel\Nova\Trix\StorePendingAttachment as TrixStorePendingAttachment;

class StorePendingImage extends TrixStorePendingAttachment
{
    use ValidatesRequests;

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
                'attachment' => 'mimes:jpeg,png,jpg,gif|required|dimensions:min_width=150,min_height=150|max:5000',
                'draftId'    => 'required',
            ],
            [
                'mimes'      => 'You must use a valid jpeg, png, jpg or gif image.',
                'max'        => 'The image must be less than 5MB.',
                'dimensions' => 'The image must be at least 150px wide and 150px tall.',
            ]
        );

        /** @var UploadedFile $file */
        $file = $request->file('attachment');
        /** @var string $storageDir */
        $storageDir = $this->field->getStorageDir();
        /** @var string $disk */
        $disk = $this->field->getStorageDisk();
        /** @var string $draftId */
        $draftId = (string) $request->input('draftId');

        $attachment = $file->store($storageDir, $disk);

        $attachment = PendingAttachment::create([
            'draft_id'   => $draftId,
            'attachment' => $attachment,
            'disk'       => $disk,
        ]);

        /** @var FilesystemAdapter $storage */
        $storage = Storage::disk($disk);
        $url     = $storage->url($attachment->attachment);

        // We need to return a string to make it compatible with the parent class
        return json_encode([
            'url' => $url,
            'id' => $attachment->id,
        ]);
    }
}
