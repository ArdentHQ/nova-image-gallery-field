<?php

declare(strict_types=1);

use Ardenthq\ImageGalleryField\FieldServiceProvider;
use Illuminate\Support\Facades\Event;
use Laravel\Nova\Events\ServingNova;
use Laravel\Nova\Nova;

it('adds the image-gallery-field closure when serving nova', function () {
    Event::spy();

    $provider = new FieldServiceProvider(app());

    $provider->boot();

    Event::shouldHaveReceived('listen')->once()->with(ServingNova::class, Mockery::type('callable'));
});

it('adds the scripts when nova is serving', function () {
    (new FieldServiceProvider(app()))->boot();

    Event::dispatch(new ServingNova(request()));

    expect(Nova::$scripts)->toHaveLength(1)
        ->and(Nova::$scripts[0]->name())->toBe('image-gallery-field');
});
