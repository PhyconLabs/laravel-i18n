<?php

namespace Phycon\Translations\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Waavi\Translation\Facades\TranslationCache;
use Waavi\Translation\Models\Translation;

class TranslationsController extends Controller
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    /**
     * @param string|null $group
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function index( $group = null )
    {
        $defaultLocale = config( 'app.locale' );

        if( $group )
        {
            $translationStrings = Translation::where( 'group', $group )->get();
        }
        else
        {
            $translationStrings = Translation::where( 'group', '*' )->get();
        }

        $translations = [];

        foreach( $translationStrings as $translationString )
        {
            if( !isset( $translations[$translationString->item] ) )
            {
                $translations[$translationString->item] = [];
            }

            $translations[$translationString->item][$translationString->locale] = $translationString;
        }

        $locales = config( 'translator.available_locales' );
        $availableGroups = $this->groups();

        return view( 'translator::translations.index', compact( 'translations', 'locales', 'defaultLocale', 'group', 'availableGroups' ) );
    }

    /**
     * @param Request $request
     */
    public function update( Request $request )
    {
        $translation = Translation::where( 'locale', $request->get( 'locale' ) )
            ->where( 'group', $request->get( 'group' ) ?? '*' )
            ->where( 'item', $request->get( 'item' ) )
            ->first();

        $text = $request->get( 'text' );

        if( $translation )
        {
            if( !$text )
            {
                $translation->delete();
            }
            else
            {
                $translation->text = $text;
                $translation->save();
            }
        }
        elseif( $text )
        {
            $translation = new Translation( [
                'locale' => $request->get( 'locale' ),
                'group' => $request->get( 'group' ) ?? '*',
                'item' => $request->get( 'item' ),
                'namespace' => '*'
            ] );

            $translation->text = $text;
            $translation->save();
        }

        TranslationCache::flushAll();

        response()->json( [ 'status' => 'success' ] );
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function filter( Request $request )
    {
        $group = $request->get( 'group' );

        if( !$group || $group === '*' )
        {
            return redirect()->route( 'translations.index' );
        }

        return redirect()->route( 'translations.index', [ 'group' => $group ] );
    }

    /**
     * @return array
     */
    private function groups()
    {
        $translations = Translation::select( 'group' )->groupBy( 'group' )->get();
        $groups = [];

        foreach( $translations as $translation )
        {
            $groups[$translation->group] = $translation->group;
        }

        $groups['*'] =  __( 'General' );

        return $groups;
    }
}