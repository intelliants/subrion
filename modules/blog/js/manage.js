$(function () {
    $('#input-title, #input-slug').on('blur', function () {
        var slug = $('#input-slug').val();
        var title = slug != '' ? slug : $('#input-title').val();

        if (title) {
            intelli.post(intelli.config.url + 'blog/slug.json', {title: title}, function(data){
                if ('' !== data.url) {
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
