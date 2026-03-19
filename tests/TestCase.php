<?php

namespace VV\ContentRenderer\Tests;

use Orchestra\Testbench\TestCase as OrchestraTestCase;
use Statamic\Providers\StatamicServiceProvider;
use Statamic\Statamic;
use Statamic\Testing\Concerns\PreventsSavingStacheItemsToDisk;
use VV\ContentRenderer\ServiceProvider;

abstract class TestCase extends OrchestraTestCase
{
    use PreventsSavingStacheItemsToDisk;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();

        $this->fakeStacheDirectory = __DIR__.'/__fixtures__/dev-null';
        $this->preventSavingStacheItemsToDisk();
    }

    protected function tearDown(): void
    {
        $this->deleteFakeStacheDirectory();

        parent::tearDown();
    }

    protected function getPackageProviders($app): array
    {
        return [
            StatamicServiceProvider::class,
            ServiceProvider::class,
        ];
    }

    protected function getPackageAliases($app): array
    {
        return [
            'Statamic' => Statamic::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('statamic.users.repository', 'file');
        $app['config']->set('statamic.stache.watcher', false);
        $app['config']->set('statamic.stache.stores.taxonomies.directory', __DIR__.'/__fixtures__/content/taxonomies');
        $app['config']->set('statamic.stache.stores.terms.directory', __DIR__.'/__fixtures__/content/taxonomies');
        $app['config']->set('statamic.stache.stores.collections.directory', __DIR__.'/__fixtures__/content/collections');
        $app['config']->set('statamic.stache.stores.entries.directory', __DIR__.'/__fixtures__/content/collections');
        $app['config']->set('statamic.stache.stores.navigation.directory', __DIR__.'/__fixtures__/content/navigation');
        $app['config']->set('statamic.stache.stores.globals.directory', __DIR__.'/__fixtures__/content/globals');
        $app['config']->set('statamic.stache.stores.global-variables.directory', __DIR__.'/__fixtures__/content/globals');
        $app['config']->set('statamic.stache.stores.asset-containers.directory', __DIR__.'/__fixtures__/content/assets');
        $app['config']->set('statamic.stache.stores.nav-trees.directory', __DIR__.'/__fixtures__/content/structures/navigation');
        $app['config']->set('statamic.stache.stores.collection-trees.directory', __DIR__.'/__fixtures__/content/structures/collections');

        $app['config']->set('view.paths', [
            __DIR__.'/views',
        ]);
    }
}
