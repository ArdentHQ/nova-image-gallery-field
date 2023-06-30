<?php

declare(strict_types=1);

namespace Tests;

use Illuminate\Database\Schema\Blueprint;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpNovaAttachmentsTables($this->app);

        $this->setUpExampleModelTable($this->app);

        $this->setUpMediaLibrary($this->app);
    }

    /**
     * @param \Illuminate\Foundation\Application $app
     */
    public function getEnvironmentSetUp(
        $app
    ) {
        config()->set('database.default', 'sqlite');

        config()->set('database.connections.sqlite', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);
    }

    /**
     * @param \Illuminate\Foundation\Application $app
     */
    protected function setUpNovaAttachmentsTables($app)
    {
        $app['db']->connection()->getSchemaBuilder()->create('nova_pending_field_attachments', function (Blueprint $table) {
            $table->increments('id');
            $table->string('draft_id')->index();
            $table->string('attachment');
            $table->string('original_name');
            $table->string('disk');
            $table->timestamps();
        });

        $app['db']->connection()->getSchemaBuilder()->create('nova_field_attachments', function (Blueprint $table) {
            $table->increments('id');
            $table->string('attachable_type');
            $table->unsignedInteger('attachable_id');
            $table->string('attachment');
            $table->string('disk');
            $table->string('url')->index();
            $table->timestamps();

            $table->index(['attachable_type', 'attachable_id']);
        });
    }

    /**
     * @param \Illuminate\Foundation\Application $app
     */
    protected function setUpExampleModelTable($app)
    {
        $app['db']->connection()->getSchemaBuilder()->create('example_models', function (Blueprint $table) {
            $table->increments('id');
            $table->timestamps();
        });
    }

    /**
     * @param \Illuminate\Foundation\Application $app
     */
    protected function setUpMediaLibrary($app)
    {
        config()->set('media-library', include 'vendor/spatie/laravel-medialibrary/config/media-library.php');

        $app['db']->connection()->getSchemaBuilder()->create('media', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->morphs('model');
            $table->uuid('uuid')->nullable()->unique();
            $table->string('collection_name');
            $table->string('name');
            $table->string('file_name');
            $table->string('mime_type')->nullable();
            $table->string('disk');
            $table->string('conversions_disk')->nullable();
            $table->unsignedBigInteger('size');
            $table->json('manipulations');
            $table->json('custom_properties');
            $table->json('generated_conversions');
            $table->json('responsive_images');
            $table->unsignedInteger('order_column')->nullable()->index();

            $table->nullableTimestamps();
        });
    }
}
