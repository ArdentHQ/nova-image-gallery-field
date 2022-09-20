<?php

declare(strict_types=1);

namespace Ardenthq\ImageGalleryField;

use Illuminate\Support\ServiceProvider;
use Laravel\Nova\Events\ServingNova;
use Laravel\Nova\Nova;

class FieldServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Nova::serving(static function (ServingNova $event) {
            Nova::script('image-gallery-field', __DIR__.'/../dist/js/field.js');
            Nova::style('image-gallery-field', __DIR__.'/../dist/css/field.css');
        });
    }
}
