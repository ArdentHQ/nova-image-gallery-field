<?php

declare(strict_types=1);

use Ardenthq\ImageGalleryField\ImageGalleryField;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Nova\Fields\Attachments\PendingAttachment;
use Laravel\Nova\Http\Requests\NovaRequest;
use Tests\Fixtures\ExampleModel;

function createNovaRequest(array $files = [], array $parameters = [])
{
    return new class($parameters, $files) extends NovaRequest {
        public function __construct(array $parameters, array $files)
        {
            parent::__construct(files: $files, query: $parameters);
        }
    };
}

it('creates an instance', function () {
    $field = new ImageGalleryField('content');

    expect($field->component)->toBe('image-gallery-field')
        ->and($field->jsonSerialize())->toMatchArray([
            'uniqueKey' => 'content-default-image-gallery-field',
            'attribute' => 'content',
            'component' => 'image-gallery-field',
        ])
        ->and($field->disk)->toBe('public')
        ->and($field->storagePath)->toBe('/')
        ->and($field->withFiles)->toBe(true);
});

it('accepts custom single image rules', function () {
    $field = new ImageGalleryField('content');

    $field->rules('mimes:jpeg,png,jpg,gif', 'dimensions:min_width=150,min_height=150', 'max:5000');

    expect($field->imageRules)->toBe([
        'mimes:jpeg,png,jpg,gif',
        'dimensions:min_width=150,min_height=150',
        'max:5000',
    ]);
});

it('accepts custom single image rules messages', function () {
    $field = new ImageGalleryField('content');

    $field->rulesMessages([
        'mimes'      => 'You must use a valid jpeg, png, jpg or gif image.',
        'max'        => 'The image must be less than 5MB.',
        'dimensions' => 'The image must be at least 150px wide and 150px tall.',
    ]);

    expect($field->imageRulesMessages)->toBe([
        'mimes'      => 'You must use a valid jpeg, png, jpg or gif image.',
        'max'        => 'The image must be less than 5MB.',
        'dimensions' => 'The image must be at least 150px wide and 150px tall.',
    ]);
});

it('shows on index', function () {
    $field = new ImageGalleryField('content');

    expect($field->showOnIndex)->toBeFalse();

    $field->showOnIndex();

    expect($field->showOnIndex)->toBeTrue();
});

it('returns an array with the media library info', function () {
    Storage::fake();

    $field = new ImageGalleryField('images');

    $model = ExampleModel::create();

    $images = collect(range(1, 3))->map(function ($i) {
        return UploadedFile::fake()->image("image{$i}.png");
    })->map(function (UploadedFile $image) use ($model) {
        return $model->addMedia($image)->toMediaCollection('images');
    });

    $value = $images->map(function ($image, $index) {
        return [
            'id'    => $image->id,
            'url'   => $image->getUrl(),
            'order' => $index,
        ];
    })->toArray();

    $field->resolveForDisplay($model, 'images');

    expect($field->value->toArray())->toEqual($value);
});

it('stores the new images in the given order', function () {
    Storage::fake();

    $field = new ImageGalleryField('images');

    $model = ExampleModel::create();

    $newImages = collect(range(1, 3))->map(function ($i) {
        return UploadedFile::fake()->image("image{$i}.png");
    })->map(function (UploadedFile $image) {
        return ['hash_name' => $image->store('/', ['disk' => 'public']), 'original_name' => $image->name];
    })->map(function (array $imageArray) {
        return PendingAttachment::create([
            'draft_id'      => 'abc',
            'attachment'    => $imageArray['hash_name'],
            'original_name' => $imageArray['original_name'],
            'disk'          => 'public',
        ]);
    });

    // Ids of the pending attachments that need to be persisted
    $newImagesIds = $newImages->pluck('id')->map(fn ($id) => (string) $id);

    // The order for the new images contain a `new:` prefix
    $imageOrderIds        = [$newImagesIds->get(1), $newImagesIds->get(0), $newImagesIds->get(2)];
    $imageOrderWithPrefix = collect($imageOrderIds)->map(fn ($id) => "new:{$id}")->toArray();

    $request = createNovaRequest(parameters: [
        'images'        => $newImagesIds->toArray(),
        'images_delete' => [],
        'images_order'  => $imageOrderWithPrefix,
    ]);

    $field->fillInto(request: $request, model: $model, attribute: 'images');

    $model->save();

    expect($model->media('images')->get())->toHaveCount(3)
        ->and($model->getMedia('images')->pluck('id')->toArray())->toEqual($imageOrderIds);
});

it('stores the previously added images in the given order', function () {
    Storage::fake();

    $field = new ImageGalleryField('images');

    $model = ExampleModel::create();

    $images = collect(range(1, 3))->map(function ($i) {
        return UploadedFile::fake()->image("image{$i}.png");
    })->map(function (UploadedFile $image) use ($model) {
        return $model->addMedia($image)->toMediaCollection('images');
    });

    // Ids of the pending attachments that need to be persisted
    $imagesId = $images->pluck('id')->map(fn ($id) => (string) $id);

    // The order for the new images contain a `new:` prefix
    $imageOrderIds        = [$imagesId->get(1), $imagesId->get(0), $imagesId->get(2)];

    $request = createNovaRequest(parameters: [
        'images'        => [],
        'images_delete' => [],
        'images_order'  => $imageOrderIds,
    ]);

    $field->fillInto(request: $request, model: $model, attribute: 'images');

    $model->save();

    expect($model->media('images')->get())->toHaveCount(3)
        ->and($model->getMedia('images')->pluck('id')->toArray())->toEqual($imageOrderIds);
});

it('deletes the given images to delete', function () {
    Storage::fake();

    $field = new ImageGalleryField('images');

    $model = ExampleModel::create();

    $images = collect(range(1, 3))->map(function ($i) {
        return UploadedFile::fake()->image("image{$i}.png");
    })->map(function (UploadedFile $image) use ($model) {
        return $model->addMedia($image)->toMediaCollection('images');
    });

    // Ids of the pending attachments that need to be persisted
    $imagesId = $images->pluck('id')->map(fn ($id) => (string) $id);

    // The order for the new images contain a `new:` prefix
    $imagesToDeleteId = [$imagesId->get(2), $imagesId->get(0)];

    $request = createNovaRequest(parameters: [
        'images'        => [],
        'images_delete' => $imagesToDeleteId,
        'images_order'  => [],
    ]);

    $field->fillInto(request: $request, model: $model, attribute: 'images');

    $model->save();

    expect($model->media('images')->get())->toHaveCount(1)
        ->and($model->getMedia('images')->first()->id)->toEqual($imagesId->get(1));
});

it('creates and stores images with correct name and path', function () {
    Storage::fake();

    $field = new ImageGalleryField('images');

    $model = ExampleModel::create();

    $newImages = collect(range(1, 3))->map(function ($i) {
        return UploadedFile::fake()->image("sample-image-name-{$i}.png");
    })->map(function (UploadedFile $image) {
        return ['hash_name' => $image->store('/', ['disk' => 'public']), 'original_name' => $image->name];
    })->map(function (array $imageArray) {
        return PendingAttachment::create([
            'draft_id'      => 'abc',
            'attachment'    => $imageArray['hash_name'],
            'original_name' => $imageArray['original_name'],
            'disk'          => 'public',
        ]);
    });

    // Ids of the pending attachments that need to be persisted
    $newImagesIds = $newImages->pluck('id')->map(fn ($id) => (string) $id);

    // The order for the new images contain a `new:` prefix
    $imageOrderIds        = [$newImagesIds->get(1), $newImagesIds->get(0), $newImagesIds->get(2)];
    $imageOrderWithPrefix = collect($imageOrderIds)->map(fn ($id) => "new:{$id}")->toArray();

    $request = createNovaRequest(parameters: [
        'images'        => $newImagesIds->toArray(),
        'images_delete' => [],
        'images_order'  => $imageOrderWithPrefix,
    ]);

    $field->fillInto(request: $request, model: $model, attribute: 'images');

    $model->save();

    $imagesFromMedia = $model->media('images')->get();

    // Asserting the files are stored correctly
    $imagesFromMedia->each(function ($image) {
        $filePath = $image['id'].'/'.$image['file_name'];
        // Assert the file was stored
        Storage::disk('public')->assertExists($filePath);

        // Assert the file name
        $imageFilePath = Storage::disk('public')->path($filePath);
        $fileName      = pathinfo($imageFilePath, PATHINFO_BASENAME);

        expect($fileName)->toBe($image['file_name']);
    });
});
