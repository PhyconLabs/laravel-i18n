<?php

\Illuminate\Support\Facades\Route::group( [ 'middleware' => [ 'web', 'auth' ] ], function () {
    \Illuminate\Support\Facades\Route::get( 'translations/{group?}', 'Phycon\Translations\Controllers\TranslationsController@index' )->name( 'translations.index' );
    \Illuminate\Support\Facades\Route::post( 'translations/filter', 'Phycon\Translations\Controllers\TranslationsController@filter' )->name( 'translations.filter' );
    \Illuminate\Support\Facades\Route::post( 'translations/{group?}', 'Phycon\Translations\Controllers\TranslationsController@update' );
} );