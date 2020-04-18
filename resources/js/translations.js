$(function () {
    $('form.translations-groups select[name="group"]').on('change', function(event) {
        let $select = $(event.target);
        let $form = $select.parents('form');

        $form.submit();
    });

    $('table.translations input[name="text"]').on( 'focusout', function(event) {
        let $input = $(event.target);
        let $table = $('table.translations');

        $input.attr('disabled', 'disabled');

        let data = {
            group: $input.data('group'),
            locale: $input.data('locale'),
            item: $input.data('item'),
            text: $input.val(),
            _token: $('meta[name=csrf-token]').attr('content')
        };

        let request = $.ajax({
            type: 'POST',
            url: $table.data('action'),
            data: data,
            headers: {
                Accept: "application/json; charset=utf-8"
            }
        });

        request.done(function() {
            setTimeout( function() {
                $input.removeAttr('disabled');
            }, 500 );
        });
    });
});