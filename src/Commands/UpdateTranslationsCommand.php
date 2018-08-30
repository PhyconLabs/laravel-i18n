<?php
/**
 * Created by PhpStorm.
 * User: maris
 * Date: 28/08/2018
 * Time: 15:33
 */

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
            resource_path( 'views' ),
        ];

        foreach( $directories as $directory )
        {
            $this->getTranslationKeysFromDir( $translationStrings, $directory );
        }

        ksort( $translationStrings );

        foreach( $translationStrings as $translationString )
        {
            if( mb_strpos( $translationString, '.' ) )
            {
                $parts = explode( '.', $translationString );
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

            // If the translation already exists, we update the text:
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