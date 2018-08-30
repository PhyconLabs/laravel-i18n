<?php

namespace Phycon\Translations;

use Phycon\Translations\Commands\UpdateTranslationsCommand;
use Phycon\Translations\Middleware\LocaleMiddleware;
use Waavi\Translation\Commands\CacheFlushCommand;
use Waavi\Translation\Loaders\CacheLoader;
use Waavi\Translation\Loaders\DatabaseLoader;
use Waavi\Translation\Repositories\TranslationRepository;
use Waavi\Translation\Routes\ResourceRegistrar;
use Waavi\Translation\UriLocalizer;
use Waavi\Translation\Cache\RepositoryFactory as CacheRepositoryFactory;

class TranslationServiceProvider extends \Waavi\Translation\TranslationServiceProvider
{
    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes( [
            __DIR__ . '/../config/translator.php' => config_path( 'translator.php' ),
        ] );

        $this->publishes( [
            __DIR__ . '/../resources/assets' => resource_path( 'assets' ),
        ] );

        $this->loadMigrationsFrom( __DIR__ . '/../database/migrations/' );
        $this->loadRoutesFrom( __DIR__ . '/../routes/web.php' );
        $this->loadViewsFrom( __DIR__ . '/../resources/views', 'translator' );
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom( __DIR__ . '/../config/translator.php', 'translator' );

        parent::register();
        $this->registerCacheRepository();
        $this->registerFileLoader();
        $this->registerCacheFlusher();
        $this->app->singleton( 'translation.uri.localizer', UriLocalizer::class );
        $this->app[\Illuminate\Routing\Router::class]->aliasMiddleware( 'locale', LocaleMiddleware::class );
        $this->app->bind( 'Illuminate\Routing\ResourceRegistrar', ResourceRegistrar::class );
    }

    /**
     *  IOC alias provided by this Service Provider.
     *
     * @return array
     */
    public function provides()
    {
        return array_merge( parent::provides(), [ 'translation.cache.repository', 'translation.uri.localizer', 'translation.loader' ] );
    }

    /**
     * Register the translation line loader.
     *
     * @return void
     */
    protected function registerLoader()
    {
        $this->app->singleton( 'translation.loader', function ( $app ) {
            $defaultLocale = $app['config']->get( 'app.locale' );
            $loader = new DatabaseLoader( $defaultLocale, $app->make( TranslationRepository::class ) );

            if( $app['config']->get( 'translator.cache.enabled' ) )
            {
                $loader = new CacheLoader( $defaultLocale, $app['translation.cache.repository'], $loader, $app['config']->get( 'translator.cache.timeout' ) );
            }

            return $loader;
        } );
    }

    /**
     *  Register the translation cache repository
     *
     * @return void
     */
    public function registerCacheRepository()
    {
        $this->app->singleton( 'translation.cache.repository', function ( $app ) {
            $cacheStore = $app['cache']->getStore();

            return CacheRepositoryFactory::make( $cacheStore, $app['config']->get( 'translator.cache.suffix' ) );
        } );
    }

    /**
     * Register the translator:load language file loader.
     *
     * @return void
     */
    protected function registerFileLoader()
    {
        $command = new UpdateTranslationsCommand();
        $this->app['command.translator:update'] = $command;
        $this->commands( 'command.translator:update' );
    }

    /**
     *  Flushes the translation cache
     *
     * @return void
     */
    public function registerCacheFlusher()
    {
        //$cacheStore      = $this->app['cache']->getStore();
        //$cacheRepository = CacheRepositoryFactory::make($cacheStore, $this->app['config']->get('translator.cache.suffix'));
        $command = new CacheFlushCommand( $this->app['translation.cache.repository'], $this->app['config']->get( 'translator.cache.enabled' ) );

        $this->app['command.translator:flush'] = $command;
        $this->commands( 'command.translator:flush' );
    }
}