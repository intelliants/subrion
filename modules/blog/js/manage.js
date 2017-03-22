$(function () {
    $('#input-title, #input-alias').on('blur', function () {
        var alias = $('#input-alias').val();
        var title = alias != '' ? alias : $('#input-title').val();

        if ('' != title) {
            $.post(intelli.config.ia_url + 'blog/alias.json', {alias: 'alias', title: title}, function (data) {
                if ('' != data.url) {
                    $('#title_url').text(data.url);
                    $('#title_box').fadeIn();
                }
                else {
                    $('#title_box').hide();
                }
            });
        }
        else {
            $('#title_box').hide();
        }
    });

    $('#input-tag').tagsInput({width: '100%', height: 'auto'});
});
