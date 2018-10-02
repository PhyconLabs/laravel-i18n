<?php

namespace Phycon\Translations\Middleware;

use Closure;
use Illuminate\Config\Repository as Config;
use Illuminate\Config\Repository;
use Illuminate\Foundation\Application;
use Illuminate\View\Factory as ViewFactory;
use Waavi\Translation\Repositories\LanguageRepository;
use Waavi\Translation\UriLocalizer;

class LocaleMiddleware
{
    /**
     *  Constructor
     *
     * @param  UriLocalizer $uriLocalizer
     * @param  LanguageRepository $languageRepository
     * @param  Repository $config
     * @param  ViewFactory $viewFactory
     * @param  Application
     */
    public function __construct( UriLocalizer $uriLocalizer, LanguageRepository $languageRepository, Config $config, ViewFactory $viewFactory, Application $app )
    {
        $this->uriLocalizer = $uriLocalizer;
        $this->languageRepository = $languageRepository;
        $this->config = $config;
        $this->viewFactory = $viewFactory;
        $this->app = $app;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \Closure $next
     * @param  integer $segment Index of the segment containing locale info
     * @return mixed
     */
    public function handle( $request, Closure $next, $segment = 0 )
    {
        // Ignores all non GET requests:
        if( $request->method() !== 'GET' )
        {
            return $next( $request );
        }

        $currentUrl = $request->getUri();
        $uriLocale = $this->uriLocalizer->getLocaleFromUrl( $currentUrl, $segment );
        $defaultLocale = $this->config->get( 'app.locale' );

        // If a locale was set in the url:
        if( $uriLocale )
        {
            if( $uriLocale === $defaultLocale )
            {
                return redirect( $this->uriLocalizer->cleanUrl( $currentUrl ) );
            }

            $currentLanguage = $this->languageRepository->findByLocale( $uriLocale );
            $selectableLanguages = $this->languageRepository->allExcept( '' );
            $altLocalizedUrls = [];

            foreach( $selectableLanguages as $lang )
            {
                $altLocalizedUrls[] = [
                    'locale' => $lang->locale,
                    'name' => $lang->name,
                    'url' => $lang->locale === $defaultLocale ? $this->uriLocalizer->cleanUrl( $currentUrl ) : $this->uriLocalizer->localize( $currentUrl, $lang->locale, $segment ),
                ];
            }

            // Set app locale
            $this->app->setLocale( $uriLocale );

            // Share language variable with views:
            $this->viewFactory->share( 'currentLanguage', $currentLanguage );
            $this->viewFactory->share( 'selectableLanguages', $selectableLanguages );
            $this->viewFactory->share( 'altLocalizedUrls', $altLocalizedUrls );

            return $next( $request );
        }

        // If a locale wasn't set in the url
        $currentLanguage = $this->languageRepository->findByLocale( $defaultLocale );
        $selectableLanguages = $this->languageRepository->allExcept( '' );
        $altLocalizedUrls = [];
        foreach( $selectableLanguages as $lang )
        {
            $altLocalizedUrls[] = [
                'locale' => $lang->locale,
                'name' => $lang->name,
                'url' => $lang->locale === $defaultLocale ? $this->uriLocalizer->cleanUrl( $currentUrl ) : $this->uriLocalizer->localize( $currentUrl, $lang->locale, $segment ),
            ];
        }

        // Set app locale
        $this->app->setLocale( $defaultLocale );

        // Share language variable with views:
        $this->viewFactory->share( 'currentLanguage', $currentLanguage );
        $this->viewFactory->share( 'selectableLanguages', $selectableLanguages );
        $this->viewFactory->share( 'altLocalizedUrls', $altLocalizedUrls );

        return $next( $request );
    }
}
