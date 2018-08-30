@extends( 'layouts.' . config( 'translator.layout' ) )

@section('content')

    <form method="post" action="{{ route( 'translations.filter' ) }}" class="translations-groups">
        @csrf

        <div class="row">
            <div class="col-lg-4">
                <div class="form-group">
                    <label for="group" class="control-label">
                        {{ __( 'Group' ) }}
                    </label>

                    <select id="group" class="form-control" name="group">
                        @foreach( $availableGroups as $value => $availableGroup )
                            <option @if( $group == $availableGroup ) selected @endif value="{{ $value }}"> {{ $availableGroup }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>
    </form>

    <div class="table-responsive">
        <table class="table table-hover translations" data-action="{{ route( 'translations.index' ) }}">
            <thead>
            <tr>
                <th></th>
                @foreach( $locales as $locale )
                    <th>{{ $locale }}</th>
                @endforeach
            </tr>
            </thead>
            <tbody>
            @foreach( $translations as $string => $translation )
                <tr>
                    <td>{{ $string }}</td>

                    @foreach( $locales as $locale )
                        <td>
                            <div class="form-group">
                                @isset( $translation[$locale] )
                                    <input type="text" name="text" value="{{ $translation[$locale]->text }}" class="form-control" data-group="{{ $translation[$locale]->group }}" data-locale="{{ $locale }}" data-item="{{ $translation[$locale]->item }}">
                                @else
                                    <input type="text" name="text" class="form-control" data-group="{{ $translation[$defaultLocale]->group }}" data-locale="{{ $locale }}" data-item="{{ $translation[$defaultLocale]->item }}">
                                @endif
                            </div>
                        </td>
                    @endforeach
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>

@endsection