<?php

namespace Phycon\Translations\Commands;


use Illuminate\Console\Command;
use Waavi\Translation\Models\Translation;

class UpdateTranslationsCommand extends Command
{
    /**
     * @var string
     */
    protected $name = 'translator:update';

    /**
     * @var string
     */
    protected $description = "Load translation strings into the database.";

    /**
     * @return void
     */
    public function handle()
    {
        $this->loadTranslationStrings();
    }

    /**
     * @return void
     */
    protected function loadTranslationStrings()
    {
        $translationStrings = [];
        $defaultLocale = config( 'app.locale' );

        $directories = [
            app_path(),
            resource_path(),
        ];

        foreach( $directories as $directory )
        {
            $this->getTranslationKeysFromDir( $translationStrings, $directory );
            $this->getTranslationKeysFromDir( $translationStrings, $directory, 'vue' );
        }

        $validationMessages = require_once resource_path( 'lang/en/validation.php' );

        foreach( $validationMessages as $translationKey => $translationMessage )
        {
            if( is_array( $translationMessage ) )
            {
                foreach( $translationMessage as $subTranslationKey => $subTranslationMessage )
                {
                    $translation = Translation::whereLocale( $defaultLocale )
                        ->whereNamespace( '*' )
                        ->whereGroup( 'validation' )
                        ->whereItem( $translationKey . '.' . $subTranslationKey )
                        ->first();

                    if( !$translation )
                    {
                        $translation = new Translation( [
                            'namespace' => '*',
                            'group' => 'validation',
                            'item' => $translationKey . '.' . $subTranslationKey,
                            'text' => $subTranslationMessage,
                            'locale' => $defaultLocale
                        ] );

                        $translation->save();
                    }
                }
            }
            else
            {
                $translation = Translation::whereLocale( $defaultLocale )
                    ->whereNamespace( '*' )
                    ->whereGroup( 'validation' )
                    ->whereItem( $translationKey )
                    ->first();

                if( !$translation )
                {
                    $translation = new Translation( [
                        'namespace' => '*',
                        'group' => 'validation',
                        'item' => $translationKey,
                        'text' => $translationMessage,
                        'locale' => $defaultLocale
                    ] );

                    $translation->save();
                }
            }
        }

        ksort( $translationStrings );

        foreach( $translationStrings as $translationString )
        {
            if( mb_strpos( $translationString, '.' ) )
            {
                $parts = explode( '.', $translationString, 2 );
                $group = $parts[0];
                $translationString = $parts[1];
            }
            else
            {
                $group = '*';
            }

            $translation = Translation::whereLocale( $defaultLocale )
                ->whereNamespace( '*' )
                ->whereGroup( $group )
                ->whereItem( $translationString )
                ->first();

            if( !$translation )
            {
                $translation = new Translation( [
                    'namespace' => '*',
                    'group' => $group,
                    'item' => $translationString,
                    'text' => $translationString,
                    'locale' => $defaultLocale
                ] );

                $translation->save();
            }
        }
    }

    /**
     * @param array $translationStrings
     * @param string $dirPath
     * @param string $fileExt
     */
    private function getTranslationKeysFromDir( &$translationStrings, $dirPath, $fileExt = 'php' )
    {
        $files = glob_recursive( "{$dirPath}/*.{$fileExt}", GLOB_BRACE );

        foreach( $files as $file )
        {
            $content = $this->getSanitizedContent( $file );
            $this->getTranslationKeysFromFunction( $translationStrings, '__', $content );
        }

        $adminMenuLinks = config( 'larmin.menu.admin', [] );
        $publicMenuLinks = config( 'larmin.menu.public', [] );

        foreach( $adminMenuLinks as $title => $options )
        {
            $translationStrings[$title] = $title;
        }

        foreach( $publicMenuLinks as $title => $options )
        {
            $translationStrings[$title] = $title;
        }
    }

    /**
     * @param array $keys
     * @param string $functionName
     * @param string $content
     */
    private function getTranslationKeysFromFunction( &$keys, $functionName, $content )
    {
        $matches = [];
        preg_match_all( "#{$functionName}\((.*?)\)#", $content, $matches );

        if( !empty( $matches ) )
        {
            foreach( $matches[1] as $match )
            {
                $strings = [];
                preg_match( '#\'(.*?)\'#', str_replace( '"', "'", $match ), $strings );

                if( !empty( $strings ) )
                {
                    $keys[$strings[1]] = $strings[1];
                }
            }
        }
    }

    /**
     * @param string $filePath
     * @return string
     */
    private function getSanitizedContent( $filePath )
    {
        return str_replace( "\n", ' ', file_get_contents( $filePath ) );
    }
}