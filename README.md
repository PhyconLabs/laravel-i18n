# laravel-languages

This package combines functionality from [Waavi/translation](https://github.com/Waavi/translation) and [thiagocordeiro/laravel-translator](https://github.com/thiagocordeiro/laravel-translator).

## Installation
1. Require through composer
```
composer require phyconlabs/laravel-languages
```

2. Publish vendor assets and configuration file
```
php artisan vendor:publish --provider="Phycon\Translations\TranslationServiceProvider"
```

3. Replace Laravel default Translation service provider in `config/app.php`
```
Illuminate\Translation\TranslationServiceProvider::class
```
with:
```
\Phycon\Translations\TranslationServiceProvider::class
```
4. Migrate the translation and locale tables
```
php artisan migrate
```

5. Include `/resources/js/translations.js` in your admin section/layout.

## Configuration
1. Set `available_locales` in `/config/translator.php`
2. Set `layout` for translations UI to extend in `/config/translator.php`
3. Insert available locales in `translator_languages` table

## Usage
### Routes
In your `web.php` route file add `locale` middleware for routes that need multilingual content and locale in the url
```
$localizer = \App::make( \Waavi\Translation\UriLocalizer::class );

Route::group( [ 'prefix' => $localizer->localeFromRequest(), 'middleware' => 'locale' ], function () {
    Route::get( 'test', 'TestController@index' )->name( 'test' );
});
```

### Scan project files for translation strings
Use artisan command `php artisan translator:update`. This scans the `app` and `views` directories for usages of `__( 'text' )` function and populates the `translator_translations` table.

### Manage translations
String translations can be edited at `/translations` route, any changes are saved on field focusOut event.
