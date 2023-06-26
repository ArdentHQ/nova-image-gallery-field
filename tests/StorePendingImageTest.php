<?php

declare(strict_types=1);

use Ardenthq\ImageGalleryField\ImageGalleryField;
use Ardenthq\ImageGalleryField\StorePendingImage;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Laravel\Nova\Fields\Attachments\PendingAttachment;

function createRequestWithFile(UploadedFile $file, array $parameters = [])
{
    return new class($file, $parameters) extends Request {
        public function __construct(UploadedFile $file, array $parameters)
        {
            parent::__construct(files: ['attachment' => $file], query: $parameters);
        }
    };
}

it('creates an instance', function () {
    $field = new ImageGalleryField('content');

    $job = new StorePendingImage($field);

    expect($job->field)->toBe($field);
});

it('requires an attachment', function () {
    $field = new ImageGalleryField('content');

    $job = new StorePendingImage($field);

    $job->__invoke(request()->merge([
        'draftId' => '123',
    ]));
})->throws(ValidationException::class, 'The attachment field is required.');

it('requires a draftId', function () {
    $field = new ImageGalleryField('content');

    $job = new StorePendingImage($field);

    $job->__invoke(request()->merge([
        'attachment' => UploadedFile::fake()->image('image.jpg'),
    ]));
})->throws(ValidationException::class, 'The draft id field is required.');

it('stores the attachment and returns a json with the url and model id', function () {
    Storage::fake('public');

    $field = new ImageGalleryField('content');

    $job = new StorePendingImage($field);

    $image = UploadedFile::fake()->image('image.png');

    $request = createRequestWithFile($image, [
        'draftId' => '123',
    ]);

    $response = $job->__invoke($request);

    expect(PendingAttachment::count())->toBe(1);

    $attachment = PendingAttachment::first();

    expect($attachment->draft_id)->toBe('123')
        ->and($attachment->disk)->toBe('public')
        ->and($attachment->attachment)->toEndWith('.png')
        ->and($attachment->original_name)->toBe($image->name)
        ->and($response)->toBeJson()
        ->and($response)->toContain(sprintf('{"url":"\/storage\/%s","id":%s}', str_replace('/', '\/', $attachment->attachment), $attachment->id));

});


