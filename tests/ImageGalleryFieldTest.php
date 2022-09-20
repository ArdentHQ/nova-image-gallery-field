<?php

declare(strict_types=1);

use Ardenthq\ImageGalleryField\ImageGalleryField;

it('creates an instance', function () {
    $field = new ImageGalleryField('content');

    expect($field->component)->toBe('image-gallery-field');

    expect($field->jsonSerialize())->toMatchArray([
        'uniqueKey' => 'content-default-image-gallery-field',
        'attribute' => 'content',
        'component' => 'image-gallery-field',
    ]);
});
